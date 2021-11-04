<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="input-group sortable-no-reorder">
	<?php if (!empty($preaddon)): ?>
        <span class="input-group-addon preaddon" <?php foreach ($preaddonAttr as $k => $v) {
			printf('%s="%s" ', $view->escape($k), $view->escape($v));
		} ?>>
    <i class="<?php echo $preaddon; ?>"></i>
    </span>
	<?php endif; ?>
    <div>
        <div class="row">
			<?php echo $view['form']->widget($form['id']); ?>
            <div class="col-xs-4 mr-0 pr-0 bdr-r-wdh-0">
				<?php echo $view['form']->widget(
					$form['answer'],
					[
						'attr' => [
							'class' => 'form-control sortable-label',
							'placeholder' => $form['answer']->vars['label']
						]
					]
				); ?>
            </div>
            <div class="col-xs-4 ml-0 pr-0 pl-0">
				<?php echo $view['form']->widget(
					$form['field'],
					[
						'attr' => [
							'class' => 'form-control sortable-value',
							'placeholder' => $form['field']->vars['label']
						]
					]); ?>
            </div>
            <div class="col-xs-4 ml-0 pl-0">
				<?php echo $view['form']->widget(
					$form['set_value'],
					[
						'attr' => [
							'class' => 'form-control sortable-value',
							'placeholder' => $form['set_value']->vars['label']
						]
					]); ?>
            </div>
        </div>
    </div>
	<?php if (!empty($postaddon)): ?>
        <span class="input-group-addon postaddon" <?php foreach ($postaddonAttr as $k => $v) {
			printf('%s="%s" ', $view->escape($k), $view->escape($v));
		} ?>>
        <i class="<?php echo $postaddon; ?>"></i>
    </span>
	<?php endif; ?>
</div>
