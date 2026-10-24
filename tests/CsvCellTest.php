<?php
use Modulix\Security\CsvCell;
use PHPUnit\Framework\TestCase;

final class CsvCellTest extends TestCase {
    public function test_encodes_and_neutralizes_spreadsheet_formula_prefixes(): void {
        $dangerous = [
            '=1+1',
            '+cmd',
            '-2+3',
            '@SUM(A1:A2)',
            "\tformula",
            "\rformula",
        ];

        foreach ($dangerous as $cell) {
            $this->assertSame('"' . "'" . $cell . '"', CsvCell::encode($cell));
        }
    }

    public function test_encodes_quotes_commas_and_newlines_as_one_rfc_4180_field(): void {
        $cells = [
            'safe",=1+1,"safe',
            'comma,separated',
            "line one\nline two",
            'a "quoted" value',
        ];

        foreach ($cells as $cell) {
            $encoded = CsvCell::encode($cell);
            $this->assertSame([$cell], str_getcsv($encoded, ',', '"', ''));
            $this->assertStringStartsWith('"', $encoded);
            $this->assertStringEndsWith('"', $encoded);
        }
    }

    public function test_encodes_safe_and_empty_cells(): void {
        $this->assertSame('"plain text"', CsvCell::encode('plain text'));
        $this->assertSame('""', CsvCell::encode(''));
        $this->assertSame('"  =not-at-start"', CsvCell::encode('  =not-at-start'));
        $this->assertSame('"\'already-neutralized"', CsvCell::encode("'already-neutralized"));
    }

    public function test_csv_export_encodes_base_headers_and_delegates_every_cell(): void {
        $source = (string) file_get_contents(__DIR__ . '/../modules/forms/submissions/export/csv-export.php');

        $this->assertStringNotContainsString('wp_json_encode', $source);
        $this->assertSame(7, substr_count($source, '=> $this->encode_cell( esc_html__('));
        $this->assertStringContainsString('return CsvCell::encode( (string) $value );', $source);
    }
}
