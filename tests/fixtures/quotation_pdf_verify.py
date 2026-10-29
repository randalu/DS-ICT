from pathlib import Path
import json
import re
from pypdf import PdfReader

root = Path(__file__).resolve().parents[2] / 'output' / 'quotation'
report = {}
for name, days in [('short', 2), ('eight-day', 8), ('long', 18)]:
    reader = PdfReader(root / f'{name}.pdf')
    pages = [p.extract_text() for p in reader.pages]
    assert all('ISLAND CULTURE TRAVELS' in p for p in pages), 'Missing repeated header'
    assert all('Private Sri Lanka journeys' in p for p in pages), 'Missing repeated footer'
    assert all(f'{i} / {len(pages)}' in p for i, p in enumerate(pages, 1)), 'Missing page numbers'
    normalized = re.sub(r'\s+', ' ', ' '.join(pages))
    assert all(f'Day {day} ' in normalized for day in range(days)), 'Day heading lost during pagination'
    assert normalized.count('Scenic rail journey') == days, 'Experience lost during pagination'
    assert normalized.count('Option B: Optional - extra charge') == days, 'Activity status lost during pagination'
    for detail in ['Example Hill Hotel', 'Example Tea Lodge', 'Double room with child arrangement',
                   'Two adults sharing, one child', 'Breakfast and dinner', 'USD 400.00', 'USD 1530.00',
                   'Driver accommodation and meals', 'Imran Mihiran']:
        assert detail in normalized, f'Document detail missing: {detail}'
    if name == 'long':
        assert normalized.count('Travel through the hills with time for scenic stops and rest.') == 65, 'Long-day text lost during pagination'
    assert 'USD 1930.00' in normalized and 'USD 1210.00' in normalized, 'Prices missing'
    report[name] = {'pages': len(pages), 'bytes': (root / f'{name}.pdf').stat().st_size, 'headers_footers_numbers': True}
print(json.dumps(report, indent=2))
