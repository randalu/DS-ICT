-- Tour-owned route, daily highlights, accommodation and detailed planning sections.
SET @program_ddl = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tours' AND column_name='program_json')=0,
 'ALTER TABLE tours ADD COLUMN program_json LONGTEXT NULL','SELECT 1');
PREPARE program_stmt FROM @program_ddl;
EXECUTE program_stmt;
DEALLOCATE PREPARE program_stmt;
