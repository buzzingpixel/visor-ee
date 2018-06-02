<?php

/** @var \EllisLab\ExpressionEngine\Service\View\View $this */
/** @var array $tableViewData */

?>

<!-- <header class="section-header">
    <div class="section-header__controls">
        <a class="btn action" href="admin.php?/cp/publish/create/1">New in Test Channel 1</a>
        <div class="filter-item filter-item--right">
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
        </div>
    </div>
</header> -->

<?php $this->embed('ee:_shared/table', $tableViewData) ?>

<fieldset class="tbl-bulk-act hidden" style="display: none;">
    <select name="bulk_action">
        <option value="">-- with selected --</option>
        <option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-entry">Remove</option>
    </select>
    <button class="btn submit" data-conditional-modal="confirm-trigger">Submit</button>
</fieldset>
