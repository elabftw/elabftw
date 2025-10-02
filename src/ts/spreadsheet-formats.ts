// Mappings for spreadsheet-editor.jsx usage
import type { BookType } from '@e965/xlsx';
import { FileType } from './interfaces';

export const BOOK_TYPE_MAP: Partial<Record<FileType, BookType>> = {
  [FileType.Csv]:  'csv',
  [FileType.Ods]: 'ods',
  [FileType.Xls]:  'xls',
  [FileType.Xlsx]: 'xlsx',
  [FileType.Xlsb]: 'xlsb',
};

export const MIME_MAP: Partial<Record<FileType, string>> = {
  [FileType.Csv]:  'text/csv',
  [FileType.Ods]:  'application/vnd.oasis.opendocument.spreadsheet',
  [FileType.Xls]:  'application/vnd.ms-excel',
  [FileType.Xlsx]: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  [FileType.Xlsb]: 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
};

// just some small helpers so call sites stay tiny
export function getBookType(fileType: FileType = FileType.Csv): BookType {
  return BOOK_TYPE_MAP[fileType] ?? 'xlsx';
}
export function getMime(fileType: FileType): string {
  return MIME_MAP[fileType] ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
}

// deduct filetype from the name, default to XLSX
export function inferFileTypeFromName(name: string): FileType {
  const ext = name.split('.').pop()?.toLowerCase();
  switch (ext) {
    case 'csv': return FileType.Csv;
    case 'ods': return FileType.Ods;
    case 'xls': return FileType.Xls;
    case 'xlsx': return FileType.Xlsx;
    case 'xlsb': return FileType.Xlsb;
    default: return FileType.Xlsx;
  }
}
