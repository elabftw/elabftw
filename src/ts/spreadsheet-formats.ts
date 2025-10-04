// Mappings for spreadsheet-editor.jsx usage
import type { BookType } from '@e965/xlsx';
import { FileType } from './interfaces';

const DEFAULT_FILETYPE: FileType = FileType.Xlsx;

const EXT_TO_FILETYPE: Record<string, FileType> = {
  csv: FileType.Csv,
  ods: FileType.Ods,
  xls: FileType.Xls,
  xlsx: FileType.Xlsx,
  xlsb: FileType.Xlsb,
};

export const BOOK_TYPE_MAP: Partial<Record<FileType, BookType>> = {
  [FileType.Csv]: 'csv',
  [FileType.Ods]: 'ods',
  [FileType.Xls]: 'xls',
  [FileType.Xlsx]: 'xlsx',
  [FileType.Xlsb]: 'xlsb',
};

export const MIME_MAP: Partial<Record<FileType, string>> = {
  [FileType.Csv]: 'text/csv',
  [FileType.Ods]: 'application/vnd.oasis.opendocument.spreadsheet',
  [FileType.Xls]: 'application/vnd.ms-excel',
  [FileType.Xlsx]: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  [FileType.Xlsb]: 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
};

// just some small helpers so call sites stay tiny
export function getBookType(fileType: FileType = DEFAULT_FILETYPE): BookType {
  return BOOK_TYPE_MAP[fileType] ?? BOOK_TYPE_MAP[DEFAULT_FILETYPE]!;
}
export function getMime(fileType: FileType): string {
  return MIME_MAP[fileType] ?? MIME_MAP[DEFAULT_FILETYPE]!;
}
// deduce filetype from name using the single ext map; default to DEFAULT_FILETYPE(xlsx)
export function inferFileTypeFromName(name: string): FileType {
  const ext = name.split('.').pop()?.toLowerCase() || '';
  return EXT_TO_FILETYPE[ext] ?? DEFAULT_FILETYPE;
}
