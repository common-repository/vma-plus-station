jQuery(document).ready(function ($) {

    updateLabels();
    // Ensure wpActiveEditor is set if it's not already
    if (typeof wpActiveEditor === 'undefined' || !wpActiveEditor) {
        wpActiveEditor = 'content'; // Default editor ID or the ID of your editor
    }
    // Handle the upload button click
    //jQuery(`#poster_image${i}_button`).click(function (e) {
    $(document).on('click', '[id^=poster_image][id$=_button]', function (e) {
        e.preventDefault(); // Prevent default action

        // Retrieve the index from the button's ID
        let buttonId = $(this).attr('id');
        let i = buttonId.match(/\d+/)[0]; // Extract the number from the ID

        // Store the target input box
        const targetInputBox = jQuery(`#poster_image${i}`);
        const targetPreview = jQuery(`#poster_image_${i}_preview`);
        const fileLabel = jQuery(`#poster_image${i}_button`);

        // Open the WordPress media editor
        const custom_uploader = wp.media({
            title: myLocalizedStrings.selectImageTitle,
            button: {
                text: myLocalizedStrings.useImageButtonText
            },
            multiple: false,
            library: {
                type: 'image' // Filter to only allow images
            }
        }).on('select', function () {
            const attachment = custom_uploader.state().get('selection').first().toJSON();
            const fileUrl = attachment.url;
            const fileName = fileUrl.substring(fileUrl.lastIndexOf('/') + 1);

            targetInputBox.val(fileUrl);
            targetPreview.attr('src', fileUrl).css('visibility', 'visible');
            fileLabel.text(fileName);
            jQuery(`#poster_image${i}_delete`).prop('disabled', false);
        }).open();
    });

});

function updateLabels() {
    for (let i = 1; i <= 18; i++) {

        // Access the file input element and its URL
        const fileInput = jQuery(`#poster_image${i}`);
        const fileUrl = fileInput.val();

        // Check if the fileUrl is not empty or null
        if (fileUrl) {
            // Extract the file name from the URL
            const fileName = fileUrl.substring(fileUrl.lastIndexOf('/') + 1);

            // Target the label element (make sure it's not the input button)
            const fileLabel = jQuery(`#poster_image${i}_button`);

            // Update the label text with the file name
            fileLabel.text(fileName);
        } else {
            // Default text if no file is selected
            jQuery(`#poster_image${i}_button`).text(myLocalizedStrings.noFileSelectedText);
        }

        // Handle the delete button click
        jQuery(`#poster_image${i}_delete`).click(function (e) {
            jQuery(`#poster_image${i}`).val("");
            jQuery(`#poster_image_${i}_preview`).attr('src', '').css('visibility', 'hidden');
            jQuery(`#poster_image${i}_delete`).prop('disabled', true);
            jQuery(`#poster_image${i}_button`).text(myLocalizedStrings.noFileSelectedText);
        });

        // Initialize preview
        if (jQuery(`#poster_image${i}`).val()) {
            jQuery(`#poster_image_${i}_preview`).attr('src', jQuery(`#poster_image${i}`).val()).css('visibility', 'visible');
            jQuery(`#poster_image${i}_delete`).css('visibility', 'visible');
            jQuery(`#poster_image${i}_delete`).prop('disabled', false);
        } else {
            jQuery(`#poster_image_${i}_preview`).css('visibility', 'hidden');
            jQuery(`#poster_image${i}_delete`).prop('disabled', true);
        }

        //handle for Select Image click
        jQuery(`#poster_image${i}_select`).on('click', function () {
            jQuery(`#poster_image${i}_button`).click();
        });
    }
}