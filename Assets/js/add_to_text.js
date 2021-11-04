mQuery(document).ready(function() {
	mQuery('.add-to-text').click(function () {
		const page = mQuery(this).closest('.add-to-text-wrapper').find('[data-get-to-text]');
		insertAtCursor(document.getElementById('message_message'), `{pagelink=${page.val()}}`);
	});
});
function insertAtCursor(field, value) {
	//IE support
	if (document.selection) {
		field.focus();
		const sel = document.selection.createRange();
		sel.text = value;
	}
	//MOZILLA and others
	else if (field.selectionStart || field.selectionStart == '0') {
		const startPos = field.selectionStart;
		const endPos = field.selectionEnd;
		field.value = field.value.substring(0, startPos)
			+ value
			+ field.value.substring(endPos, field.value.length);
	} else {
		field.value += value;
	}
}


Mautic.changeMessageType = function (e) {
	if(e.value === 'whatsapp_template') {
		mQuery('#whatsapp_template').removeClass('hide');
		mQuery('#message_template').addClass('hide');
	} else {
		mQuery('#whatsapp_template').addClass('hide');
		mQuery('#message_template').removeClass('hide');
	}
}
