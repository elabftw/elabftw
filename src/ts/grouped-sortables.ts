/**
 * Shared sortable behavior for steps and uploads, including grouping and ordering.
 * @license AGPL-3.0
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/sortable';
import { ApiC } from './api';

type GroupedItemIdsKey = 'step_ids' | 'upload_ids';

interface GroupedSortableOptions {
  itemContainerSelector: string;
  itemHandleSelector: string;
  itemIdPrefix: string;
  itemIdsKey: GroupedItemIdsKey;
  itemEndpoint: string;
  groupContainerSelector: string;
  groupItemSelector: string;
  groupHandleSelector: string;
  groupEndpoint: string;
  reload: () => Promise<void>;
  requireHandles?: boolean;
}

/** Called again on DOM reloads; destroys existing handlers before reinstating them. */
export function createGroupedSortables(options: GroupedSortableOptions): () => void {
  let orderingTimer: number | undefined;

  const syncOrdering = (): void => {
    const groupedOrdering = Array.from(document.querySelectorAll<HTMLElement>(options.itemContainerSelector)).map(container => ({
      group_id: container.dataset.groupid ? parseInt(container.dataset.groupid, 10) : null,
      [options.itemIdsKey]: Array.from(container.querySelectorAll<HTMLElement>(':scope > .countable'))
        .map(item => parseInt(item.id.replace(options.itemIdPrefix, ''), 10)),
    }));
    ApiC.patch(options.itemEndpoint, {grouped_ordering: groupedOrdering}).then(() => options.reload());
  };

  // A cross-group move fires events on the source and destination. Only send
  // the final state once, after the drag operation has completed.
  const scheduleSync = (): void => {
    window.clearTimeout(orderingTimer);
    orderingTimer = window.setTimeout(syncOrdering, 0);
  };

  return (): void => {
    const itemSortables = $(options.itemContainerSelector);
    if (itemSortables.length && (!options.requireHandles || $(options.itemHandleSelector).length)) {
      itemSortables.each(function() {
        if ($(this).hasClass('ui-sortable')) $(this).sortable('destroy');
      });
      itemSortables.sortable({
        connectWith: options.itemContainerSelector,
        items: '> .countable',
        handle: options.itemHandleSelector,
        // Sortable normally excludes buttons; both kinds of drag handle are buttons.
        cancel: 'nonSortable',
        helper: 'clone',
        dropOnEmpty: true,
        forcePlaceholderSize: true,
        placeholder: 'step-sortable-placeholder',
        tolerance: 'pointer',
        receive: scheduleSync,
        update: scheduleSync,
      });
    }

    if (options.requireHandles && !$(options.groupHandleSelector).length) return;
    $(options.groupContainerSelector).each(function() {
      if ($(this).hasClass('ui-sortable')) $(this).sortable('destroy');
      $(this).sortable({
        axis: 'y',
        items: `> ${options.groupItemSelector}`,
        handle: options.groupHandleSelector,
        cancel: 'nonSortable',
        helper: 'clone',
        forcePlaceholderSize: true,
        placeholder: 'step-group-sortable-placeholder',
        update: function() {
          const ordering = Array.from(this.querySelectorAll<HTMLElement>(`:scope > ${options.groupItemSelector}`))
            .map(group => parseInt(group.dataset.groupid, 10));
          ApiC.patch(options.groupEndpoint, {ordering}).then(() => options.reload());
        },
      });
    });
  };
}
