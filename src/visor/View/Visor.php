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

<header class="section-header">
    <form action="<?=$baseUrl?>">
        <input type="hidden" name="/cp/addons/settings/visor" value="">
        <div class="section-header__controls">
            <a class="btn action" href="#todo">New in Test Channel 1</a>
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
            <ul>
                <?php foreach ($filters as $key => $filter) : ?>
                    <li>
                        <div class="filters__item-wrapper">
                            <select name="filter[<?=$key?>][type]">
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
                            <select name="filter[<?=$key?>][operator]">
                                <option value="">--</option>
                                <option
                                    value="is"
                                    <?php if ($filter['operator'] === 'is') : ?>
                                    selected
                                    <?php endif; ?>
                                >
                                    is
                                </option>
                                <?php if ($filter['type'] !== 'channel') : ?>
                                <option
                                    value="contains"
                                    <?php if ($filter['operator'] === 'contains') : ?>
                                    selected
                                    <?php endif; ?>
                                >
                                    contains
                                </option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filters__item-wrapper">
                            <?php if ($filter['type'] === 'channel') : ?>
                                <select name="filter[<?=$key?>][value]">
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
                            <?php else : ?>
                                <input type="text" name="filter[<?=$key?>][value]" value="<?=$filter['value']?>">
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach ?>
                <li>
                    <div class="filters__item-wrapper">
                        <a href="#">Add filter</a>
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
