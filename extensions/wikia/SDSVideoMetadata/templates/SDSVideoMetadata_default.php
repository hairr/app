<div class="input-group <?= (!empty($type)) ? $type : '' ?>">
	<label for="<?= $name ?>"><?= $labelMsg ?><?= (!empty($required) && $required === true ) ? '* <small>(' . wfMsg
	('sdsvideometadata-vc-required') . ')</small>' : '' ?></label>
	<?php if (!empty($textarea) && $textarea === true): ?>
		<textarea name="<?= $name ?>" id="<?= $name ?>"><?= (!empty($value)) ? $value : '' ?></textarea>
	<?php else: ?>
	<input type="text" name="<?= $name ?>" id="<?= $name ?>" value="<?= (!empty($value)) ? $value : '' ?>">
	<?php endif; ?>
</div>