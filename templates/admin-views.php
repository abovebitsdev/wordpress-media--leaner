<div class="wrap">
    <h2>Wordpress Media Cleaner Settings Page</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label>All Media Files (including thumbnails)</label></th>
                <?php $all_uploads_images = cu_get_all_images_for_uploads();$all_images_for_uploads_original = cu_get_all_images_without_sizes($all_uploads_images);?>
                <td><?php echo count($all_uploads_images);?> (<?php echo cu_sum_image_sizes($all_uploads_images);?>Mb)</td>
            </tr>
            <tr>
                <th scope="row"><label>Uploaded Original Media Files</label></th>
                <?php $all_images_for_uploads_original = cu_get_all_images_without_sizes($all_uploads_images);?>
                <td><?php echo count($all_images_for_uploads_original);?></td>
            </tr>
            <tr class="hide-if-no-js site-icon-section">
                <th scope="row">Unused Media Files</th>
                <?php $all_unused_images = get_option('cu_get_all_unused_images', false);?>
                <td class="unused-images"><?php echo ($all_unused_images) ? count($all_unused_images) : 0;?> <?php //cu_dump($all_unused_images);?></td>
            </tr>
            <tr>
                <th scope="row"><label>Actions</label></th>
                <td>
                    <a href="#" class="button button-primary cu_scan">Scan</a>
                    <div class="spinner" style="float: unset;"></div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label></label></th>
                <td><hr/></td>
            </tr>
            <tr class="images-wrapper-tr">
                <th scope="row"><label></label></th>
                <td>
                    <input type="checkbox" class="cu_checked">
                    <a href="" class="button button-primary cu_delete">Delete <span>0</span></a>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Unused Media Files</label></th>
                <td>
                    <div class="counter">

                    </div>
                    <div class="wrapper images-wrapper" data-loading="0">

                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php
$url = admin_url('admin-ajax.php');
?>

<script>
    jQuery(document).ready(function(){
        let url = '<?php echo esc_js($url); ?>';

        <?php if(isset($all_unused_images) && is_array($all_unused_images) && count($all_unused_images)>0){?>
            loading_unused_images_f(url)
        <?php } ?>

        jQuery('.cu_scan').on('click', function(e){
            e.preventDefault();
            let data = {
                action: 'cu_scan',
            };
            jQuery('.unused-images').html('');
            jQuery('.images-wrapper').html('');
            jQuery('.spinner').addClass('is-active');
            jQuery.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response){
                    if('unused_images' in response){
                        jQuery('.unused-images').html(response.unused_images);
                        loading_unused_images_f(url)
                    }
                },
                error: function(xhr, status, error) {
                    console.error( status, error);
                },
                complete: function(){
                    jQuery('.spinner').removeClass('is-active');
                }
            });
        });

        jQuery(document).on('change', '.wrapper input[type=checkbox]', function(){
            update_checked_counter()
        });

        jQuery('.cu_delete').click(function(e){
            e.preventDefault();
            let checked = [];
            jQuery('.wrapper input[type=checkbox]').each(function() {
                if (jQuery(this).prop('checked')) {
                    checked.push(jQuery(this).val());
                }
            });

            if(checked.length > 0){
                start_progressbar(checked.length)
                delete_f(url, checked)
            }
        });

        jQuery('.cu_trash').click(function(e){
            e.preventDefault();
            let checked = [];
            jQuery('.wrapper input[type=checkbox]').each(function() {
                if (jQuery(this).prop('checked')) {
                    checked.push(jQuery(this).val());
                }
            });

            if(checked.length > 0){
                trash_f(url, checked)
            }
        });


        jQuery('.cu_checked').change(function(){
            if (jQuery(this).is(':checked')) {
                jQuery('.images-wrapper input[type="checkbox"]').prop('checked', true);
            } else {
                jQuery('.images-wrapper input[type="checkbox"]').prop('checked', false);
            }
            update_checked_counter()
        })

        jQuery('.cu_restore').click(function(e){
            e.preventDefault();
            let checked = [];
            jQuery('.wrapper input[type=checkbox]').each(function() {
                if (jQuery(this).prop('checked')) {
                    checked.push(jQuery(this).val());
                }
            });

            console.log(checked, checked.length)

            if(checked.length > 0){
                restore_f(checked)
            }
        });

        jQuery('.cu_restore_all').click(function(e){
            e.preventDefault();
            jQuery.ajax({
                url: url,
                type: 'POST',
                data: {
                    action: 'cu_restore',
                    data: 'all',
                },
                beforeSend: () => jQuery('.spinner').addClass('is-active'),
                success: function(response){
                    console.log(response)
                },
                error: function(xhr, status, error) {
                    console.error( status, error);
                },
                complete: () => jQuery('.spinner').removeClass('is-active')
            });
        });

    })

    function update_checked_counter(){
        let checkedCount = jQuery('.wrapper input[type=checkbox]:checked').length;
        jQuery('.cu_trash span').html(checkedCount);
        jQuery('.cu_delete span').html(checkedCount);
    }

    function loading_unused_images_f(url){
        let imagesWrapper = jQuery('.images-wrapper');

        if(imagesWrapper.data('loading') == '1') return;

        imagesWrapper.data('loading', '1');

        jQuery('.spinner').addClass('is-active');

        jQuery.ajax({
            url: url,
            type: 'POST',
            data: {
                action: 'cu_unused_images',
            },
            success: function(response){
                if (response.images && response.images.length > 0) {
                    start_progressbar(response.images.length)
                    add_unused_images(response.images, imagesWrapper);
                }
            },
            error: function(xhr, status, error) {
                console.error( status, error);
            },
            complete: () => {
                jQuery('.spinner').removeClass('is-active')
                if (jQuery('.images-wrapper-tr').is(':hidden')) jQuery('.images-wrapper-tr').show()
            }
        });
    }

    function add_unused_images(images, imagesWrapper){
        for (let i = 0; i < 50 && images.length > 0; i++) {
            let imageUrl = images.shift();
            let block = '<div';

            if (imageUrl.endsWith('.trash')) {
                block += ' class="trash"';
            }

            block += '>' +
                '<input type="checkbox" id="'+imageUrl+'" value="'+imageUrl+'"  />' +
                '<label for="'+imageUrl+'"><img src="'+imageUrl+'" width="120" loading="lazy">' +
                '</label>' +
                '</div>';
            imagesWrapper.append(block);
        }

        update_progressbar(images.length)

        if (images.length > 0) setTimeout(add_unused_images, 700, images, imagesWrapper);
        if (images.length == 0) {
            imagesWrapper.data('loading', '0');
            end_progressbar()
            console.log('end_progressbar');
        }
    }


    function delete_f(url, data){
        jQuery.ajax({
            url: url,
            type: 'POST',
            data: {
                action: 'cu_delete',
                data: data,
            },
            beforeSend: () => jQuery('.spinner').addClass('is-active'),
            success: function(response){
                if(response.data) {
                    update_progressbar(response.data.length)
                    delete_f(url, response.data)
                } else {
                    location.reload()
                }
            },
            error: function(xhr, status, error) {
                console.error( status, error);
            },
            complete: () => jQuery('.spinner').removeClass('is-active')
        });
    }

    function trash_f(url, data){
        jQuery.ajax({
            url: url,
            type: 'POST',
            data: {
                action: 'cu_trash',
                data: data,
            },
            beforeSend: () => jQuery('.spinner').addClass('is-active'),
            success: function(response){
                location.reload()
            },
            error: function(xhr, status, error) {
                console.error( status, error);
            },
            complete: () => jQuery('.spinner').removeClass('is-active')
        });
    }

    function restore_f(url, data){
        jQuery.ajax({
            url: url,
            type: 'POST',
            data: {
                action: 'cu_restore',
                data: data,
            },
            beforeSend: () => jQuery('.spinner').addClass('is-active'),
            success: function(response){
                location.reload()
            },
            error: function(xhr, status, error) {
                console.error( status, error);
            },
            complete: () => jQuery('.spinner').removeClass('is-active')
        });
    }

    function start_progressbar(length){
        jQuery('.counter').html('<progress id="counter" value="0" max="' + length + '"> 0% </progress>');
    }

    function update_progressbar(length){
        let max = parseInt(jQuery('.counter #counter').attr('max')),
            remaining = max - length,
            new_value = remaining;

        jQuery('.counter #counter').val(new_value);
    }

    function end_progressbar(){
        jQuery('.counter').html('')
    }

</script>

<style>
    .wrapper > div {
        display: inline-block;
        margin: 5px;
    }
    .wrapper > div input[type="checkbox"] {
        display: none;
    }
    .wrapper > div input[type="checkbox"]:checked + label {
        opacity: 0.35;
        outline: 2px solid red;
        display: block;
    }
    .images-wrapper-tr{
        display: none;
    }

    .trash{
        position: relative;
    }

    .trash::after {
        content: "Trashed";
        display: block;
        color: white;
        padding: 4px 0;
        font-weight: bold;
        position: absolute;
        bottom: 0;
        width: 100%;
        text-align: center;
        background: rgba(0,0,0,1);
    }

    progress#counter {
        width: 100%;
        height: 15px;
        color: white;
    }
</style>