// This file is auto-generated from src/Enums/State.php by src/tools/mkts.php
// Do not edit manually.

export const State = {
  Normal: 1,
  Archived: 2,
  Deleted: 3,
  Pending: 4,
  Processing: 5,
  Error: 6,
} as const;

export type StateValue = (typeof State)[keyof typeof State];
export type StateKey = keyof typeof State;

export const stateLabel: Record<StateValue, string> = {
  [State.Normal]: 'Normal',
  [State.Archived]: 'Archived',
  [State.Deleted]: 'Deleted',
  [State.Pending]: 'Pending',
  [State.Processing]: 'Processing',
  [State.Error]: 'Error',
};
