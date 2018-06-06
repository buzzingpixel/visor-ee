<?php

/** @var \EllisLab\ExpressionEngine\Service\View\View $this */
/** @var array $tableViewData */
/** @var string $baseUrl */
/** @var array $filters */
/** @var array $channelSelects */

$filterTypes = [
    '' => '--',
    'channel' => 'Channel',
    'entry_id' => 'Entry ID',
    'title' => 'Title',
    'url_title' => 'URL Title',
];

?>

<div>

    <header class="section-header">
        <form action="<?=$baseUrl?>" class="JSVisorFilterForm">
            <input type="hidden" name="/cp/addons/settings/visor" value="">
            <div class="section-header__controls">
                <!-- <a class="btn action" href="#todo">New in Test Channel 1</a> -->
                <!-- <div class="filter-item filter-item--right">
                    <a
                        href="#"
                        class="js-filter-link filter-item__link filter-item__link--has-submenu filter-item__link--action"
                    >
                        New Entry
                    </a>
                    <div class="filter-submenu">
                        <div class="filter-submenu__scroll">
                            <a href="admin.php?/cp/publish/create/1" class="filter-submenu__link">Test Channel 1</a>
                            <a href="admin.php?/cp/publish/create/1" class="filter-submenu__link">Test Channel 2</a>
                        </div>
                    </div>
                </div> -->
            </div>
            <div class="filters">
                <ul class="JSFilterList">
                    <?php foreach ($filters as $key => $filter) : ?>
                        <li class="JSFilterListItem" data-is-new="false">
                            <div class="filters__item-wrapper">
                                <select name="filter[<?=$key?>][type]" class="JSFilterType">
                                    <?php foreach ($filterTypes as $filterValue => $filterText) : ?>
                                        <option
                                            value="<?=$filterValue?>"
                                            <?php if ($filter['type'] === $filterValue) : ?>
                                            selected
                                            <?php endif ?>
                                        >
                                            <?=$filterText?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filters__item-wrapper">
                                <select name="filter[<?=$key?>][operator]" class="JSOperator">
                                    <option
                                        value="is"
                                        class="JSOperatorIs"
                                        <?php if ($filter['operator'] === 'is') : ?>
                                        selected
                                        <?php endif; ?>
                                    >
                                        is
                                    </option>
                                    <option
                                        value="contains"
                                        class="JSOperatorContains"
                                        <?php if ($filter['operator'] === 'contains') : ?>
                                        selected
                                        <?php endif; ?>
                                        <?php if ($filter['type'] === 'channel') : ?>
                                        disabled
                                        <?php endif; ?>
                                    >
                                        contains
                                    </option>
                                </select>
                            </div>
                            <div class="filters__item-wrapper">
                                <select
                                    <?php if ($filter['type'] === 'channel') : ?>
                                    name="filter[<?=$key?>][value]"
                                    <?php endif; ?>
                                    data-name="filter[<?=$key?>][value]"
                                    class="JSChannelFilterValues"
                                    <?php if ($filter['type'] !== 'channel') : ?>
                                    style="display: none;"
                                    <?php endif; ?>
                                >
                                    <?php foreach ($channelSelects as $channelName => $channelTitle) : ?>
                                        <option
                                            value="<?=$channelName?>"
                                            <?php if ($filter['value'] === $channelName) : ?>
                                            selected
                                            <?php endif ?>
                                        >
                                            <?=$channelTitle?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input
                                    type="text"
                                    <?php if ($filter['type'] !== 'channel') : ?>
                                    name="filter[<?=$key?>][value]"
                                    <?php endif; ?>
                                    <?php if ($filter['type'] !== 'channel') : ?>
                                    value="<?=$filter['value']?>"
                                    <?php endif; ?>
                                    data-name="filter[<?=$key?>][value]"
                                    class="JSOtherFilterValues"
                                    <?php if ($filter['type'] === 'channel') : ?>
                                    style="display: none;"
                                    <?php endif; ?>
                                >
                            </div>
                            <div class="filters__item-wrapper">
                                <a href="#" class="filters__remove-filter JSRemoveFilter">x</a>
                            </div>
                        </li>
                    <?php endforeach ?>
                    <li class="JSFilterControlsListItem">
                        <div class="filters__item-wrapper">
                            <a href="#" class="JSAddFilter">Add filter</a>
                        </div>
                        <div class="filters__item-wrapper">
                            <a href="<?=$baseUrl?>">Clear filters</a>
                        </div>
                        <div class="filters__item-wrapper">
                            <button>Run Filters</button>
                        </div>
                    </li>
                </ul>
            </div>
        </form>
    </header>

    <?php $this->embed('ee:_shared/table', $tableViewData) ?>

    <fieldset class="tbl-bulk-act hidden" style="display: none;">
        <select name="bulk_action">
            <option value="">-- with selected --</option>
            <option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-entry">Remove</option>
        </select>
        <button class="btn submit" data-conditional-modal="confirm-trigger">Submit</button>
    </fieldset>

</div>

<script type="text/template" id="JSFilterListItemTemplate">
    <li class="JSFilterListItem" data-is-new="true">
        <div class="filters__pending-wrapper JSPendingWrapper">
            <div class="filters__item-wrapper">
                <select name="filter[{{filterKey}}][type]" class="JSFilterType">
                    <?php foreach ($filterTypes as $filterValue => $filterText) : ?>
                        <option value="<?=$filterValue?>">
                            <?=$filterText?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters__item-wrapper">
                <select name="filter[{{filterKey}}][operator]" style="display: none;" class="JSOperator">
                    <option value="is" class="JSOperatorIs">is</option>
                    <option value="contains" class="JSOperatorContains">contains</option>
                </select>
            </div>
            <div class="filters__item-wrapper">
                <select
                    data-name="filter[{{filterKey}}][value]"
                    style="display: none;"
                    class="JSChannelFilterValues"
                >
                    <?php foreach ($channelSelects as $channelName => $channelTitle) : ?>
                        <option value="<?=$channelName?>">
                            <?=$channelTitle?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input
                    type="text"
                    data-name="filter[{{filterKey}}][value]"
                    style="display: none;"
                    class="JSOtherFilterValues"
                >
            </div>
            <div class="filters__item-wrapper">
                <a href="#" class="filters__remove-filter JSRemoveFilter">x</a>
            </div>
        </div>
    </li>
</script>
