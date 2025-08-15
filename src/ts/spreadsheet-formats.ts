// Mappings for SpreadsheetEditorHelper class & spreadsheet-editor.jsx usage
import { FileType } from './interfaces';
import type { BookType } from '@e965/xlsx';

export const BOOK_TYPE_MAP: Partial<Record<FileType, BookType>> = {
  [FileType.Csv]:  'csv',
  [FileType.Xls]:  'xls',
  [FileType.Xlsx]: 'xlsx',
  [FileType.Ods]:  'ods',
  [FileType.Fods]: 'fods',
  [FileType.Xlsb]: 'xlsb',
};

export const MIME_MAP: Partial<Record<FileType, string>> = {
  [FileType.Csv]:  'text/csv',
  [FileType.Xls]:  'application/vnd.ms-excel',
  [FileType.Xlsx]: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  [FileType.Ods]:  'application/vnd.oasis.opendocument.spreadsheet',
  [FileType.Fods]: 'application/vnd.oasis.opendocument.spreadsheet',
  [FileType.Xlsb]: 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
};

// UI list for dropdowns (See spreadsheet-editor.jsx)
export const FILE_EXPORT_OPTIONS = [
  { type: FileType.Csv, icon: 'fa-file-csv', labelKey: 'CSV' },
  { type: FileType.Xls, icon: 'fa-file-excel', labelKey: 'XLS' },
  { type: FileType.Xlsx, icon: 'fa-file-excel', labelKey: 'XLSX' },
  { type: FileType.Ods, icon: 'fa-file-excel', labelKey: 'ODS' },
  { type: FileType.Fods, icon: 'fa-file-excel', labelKey: 'FODS' },
  { type: FileType.Xlsb, icon: 'fa-file-excel', labelKey: 'XLSB' },
];

// just some small helpers so call sites stay tiny
export function getBookType(fileType: FileType): BookType {
  return BOOK_TYPE_MAP[fileType] ?? 'csv';
}
export function getMime(fileType: FileType): string {
  return MIME_MAP[fileType] ?? 'text/csv';
}
