<?php
declare(strict_types=1);
require_once __DIR__ . '/retention_settings.php';

/** Explicit spools must exist. Linux defaults are private per application/service identity. */
function ict_records_temp_directory(): string
{
    $configured = getenv('ICT_RECORDS_TMP_DIR');
    $usesLinuxDefault = PHP_OS_FAMILY === 'Linux' && (!is_string($configured) || $configured === '');
    $rawDirectory = is_string($configured) && $configured !== '' ? $configured : sys_get_temp_dir();
    $normalize = static function (string $path): string {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    };
    if ($usesLinuxDefault) {
        if (!function_exists('posix_geteuid')) { throw new RuntimeException('Could not verify the workbook service identity.'); }
        $temporaryRoot = realpath(sys_get_temp_dir());
        $applicationRoot = realpath(dirname(__DIR__));
        if ($temporaryRoot === false || $applicationRoot === false) { throw new RuntimeException('Could not verify the private workbook boundary.'); }
        $rawDirectory = $temporaryRoot . '/ict-records-' . substr(hash('sha256', $applicationRoot . ':' . posix_geteuid()), 0, 24);
    }
    $candidate = $normalize(realpath($rawDirectory) ?: $rawDirectory);
    foreach ([dirname(__DIR__), $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)] as $root) {
        $resolved = realpath($root);
        if ($resolved === false) { throw new RuntimeException('Could not verify the public directory boundary.'); }
        $public = $normalize($resolved);
        if ($candidate === $public || str_starts_with($candidate . '/', $public . '/')) { throw new RuntimeException('Workbook temporary files must stay outside public directories.'); }
    }
    if ($usesLinuxDefault) {
        if (is_link($rawDirectory)) { throw new RuntimeException('Workbook spool must not be a symbolic link.'); }
        if (!file_exists($rawDirectory) && !@mkdir($rawDirectory, 0700) && !is_dir($rawDirectory)) { throw new RuntimeException('Could not prepare the private workbook spool.'); }
        if (fileowner($rawDirectory) !== posix_geteuid()) { throw new RuntimeException('Workbook spool belongs to a different service identity.'); }
    }
    $directory = realpath($rawDirectory);
    if ($directory === false || !is_dir($directory) || !is_writable($directory)) { throw new RuntimeException('An existing writable private workbook spool is required.'); }
    if (!ict_records_spool_private($directory)) { throw new RuntimeException('Workbook spool access must be restricted to its owner, service identity and system administrators.'); }
    return $directory;
}

/** Check effective ACL entries without changing permissions or trusting a path alone. */
function ict_records_spool_private(string $directory): bool
{
    if (PHP_OS_FAMILY !== 'Windows') {
        $mode = fileperms($directory);
        return $mode !== false && ($mode & 0077) === 0;
    }
    if (!function_exists('proc_open')) { return false; }
    $encodedPath = base64_encode($directory);
    $script = '$ErrorActionPreference="Stop"; try { $path=[Text.Encoding]::UTF8.GetString([Convert]::FromBase64String("'.$encodedPath.'")); $acl=[IO.Directory]::GetAccessControl($path); $owner=$acl.GetOwner([Security.Principal.SecurityIdentifier]).Value; $current=[Security.Principal.WindowsIdentity]::GetCurrent().User.Value; $allowed=@($owner,$current,"S-1-5-18","S-1-5-32-544"); if(!$acl.AreAccessRulesProtected){exit 1}; foreach($entry in $acl.GetAccessRules($true,$true,[Security.Principal.SecurityIdentifier])) { if($entry.AccessControlType -eq "Allow" -and $entry.IdentityReference.Value -notin $allowed){exit 1} }; Write-Output "private-spool" } catch { exit 1 }';
    $process = @proc_open(['powershell.exe', '-NoProfile', '-NonInteractive', '-EncodedCommand', base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'))], [1=>['pipe','w'], 2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
    if (!is_resource($process)) { return false; }
    $output = stream_get_contents($pipes[1]); stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    return proc_close($process) === 0 && trim($output) === 'private-spool';
}

function ict_records_workbook_available(): bool
{
    try {
        if (!class_exists('ZipArchive') || !class_exists('DOMDocument') || !is_file(ICT_RECORDS_TEMPLATE)) { return false; }
        ict_records_temp_directory();
        return true;
    } catch (Throwable) { return false; }
}

/** Create a temporary blank-template snapshot; never edit the operator's working workbook. */
function ict_records_workbook_snapshot(array $input, string $template = ICT_RECORDS_TEMPLATE): string
{
    $settings = ict_retention_validate($input);
    if (!class_exists('ZipArchive') || !class_exists('DOMDocument') || !is_file($template)) {
        throw new RuntimeException('Workbook template or ZIP/XML support unavailable.');
    }
    $directory = ict_records_temp_directory();
    $path = tempnam($directory, 'ict-records-');
    if ($path === false) { throw new RuntimeException('Could not prepare workbook download.'); }
    if (realpath(dirname($path)) !== $directory || (PHP_OS_FAMILY !== 'Windows' && !chmod($path, 0600))) { unlink($path); throw new RuntimeException('Could not secure the workbook temporary file.'); }
    if (!copy($template, $path)) { unlink($path); throw new RuntimeException('Could not prepare workbook download.'); }
    $zip = new ZipArchive();
    $opened = false;
    try {
        if ($zip->open($path) !== true) { throw new RuntimeException('Could not open workbook template.'); }
        $opened = true;
        $xml = static function (string $text): DOMDocument {
            $doc = new DOMDocument();
            if ($text === '' || !@$doc->loadXML($text, LIBXML_NONET)) { throw new RuntimeException('Invalid workbook XML.'); }
            return $doc;
        };
        $book = $xml((string) $zip->getFromName('xl/workbook.xml'));
        $relationshipId = null;
        foreach ($book->getElementsByTagName('sheet') as $sheet) {
            if ($sheet->getAttribute('name') === 'Retention') { $relationshipId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id'); }
        }
        $relations = $xml((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        $entry = null;
        foreach ($relations->getElementsByTagName('Relationship') as $relation) {
            if ($relation->getAttribute('Id') === $relationshipId) {
                $target = ltrim($relation->getAttribute('Target'), '/');
                $entry = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }
        if (!is_string($entry) || !preg_match('~^xl/worksheets/sheet\d+\.xml$~', $entry)) { throw new RuntimeException('Retention worksheet missing.'); }
        $sheet = $xml((string) $zip->getFromName($entry));
        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $setCell = static function (string $ref, string|int $value) use ($sheet, $namespace): void {
            $target = null;
            foreach ($sheet->getElementsByTagName('c') as $cell) { if ($cell->getAttribute('r') === $ref) { $target = $cell; break; } }
            if (!$target instanceof DOMElement) { throw new RuntimeException('Required template cell missing: ' . $ref); }
            while ($target->firstChild) { $target->removeChild($target->firstChild); }
            if (is_int($value)) {
                $target->removeAttribute('t');
                $target->appendChild($sheet->createElementNS($namespace, 'v', (string) $value));
            } else {
                $target->setAttribute('t', 'inlineStr');
                $inline = $sheet->createElementNS($namespace, 'is');
                $text = $sheet->createElementNS($namespace, 't');
                $text->appendChild($sheet->createTextNode($value));
                $inline->appendChild($text); $target->appendChild($inline);
            }
        };
        $setCell('B3', 'Downloaded ' . (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Y-m-d H:i') . ' Sri Lanka time');
        $setCell('B4', substr(hash('sha256', json_encode($settings, JSON_THROW_ON_ERROR)), 0, 16));
        $row = 6;
        foreach (ict_retention_categories() as $key => $category) {
            $setCell('C' . $row, $settings['rules'][$key]['period']);
            $setCell('D' . $row, $settings['rules'][$key]['unit']);
            $setCell('E' . $row, $settings['rules'][$key]['trigger']);
            $row++;
        }
        foreach (['hold_note' => 'B18', 'copies_note' => 'B20', 'storage_note' => 'B22'] as $key => $ref) { $setCell($ref, $settings[$key]); }
        $calculation = $book->getElementsByTagName('calcPr')->item(0);
        if (!$calculation) { $calculation = $book->createElementNS($namespace, 'calcPr'); $book->documentElement->appendChild($calculation); }
        $calculation->setAttribute('calcMode', 'auto'); $calculation->setAttribute('fullCalcOnLoad', '1'); $calculation->setAttribute('forceFullCalc', '1');
        if (!$zip->addFromString('xl/workbook.xml', $book->saveXML())) { throw new RuntimeException('Could not write workbook metadata.'); }
        // Empty template formulas should recalculate against this download's settings in Excel.
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (!preg_match('~^xl/worksheets/sheet\d+\.xml$~', $name)) { continue; }
            $doc = $name === $entry ? $sheet : $xml((string) $zip->getFromName($name));
            foreach ($doc->getElementsByTagName('c') as $cell) {
                if ($cell->getElementsByTagName('f')->length === 0) { continue; }
                foreach (iterator_to_array($cell->getElementsByTagName('v')) as $cached) { $cell->removeChild($cached); }
            }
            if (!$zip->addFromString($name, $doc->saveXML())) { throw new RuntimeException('Could not write workbook worksheet.'); }
        }
        $closed = $zip->close(); $opened = false;
        if (!$closed) { throw new RuntimeException('Could not finalize workbook download.'); }
        return $path;
    } catch (Throwable $exception) {
        if ($opened) { $zip->close(); }
        if (is_file($path)) { unlink($path); }
        throw $exception;
    }
}
