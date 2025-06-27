<?php
/**
 * Admin list table template for design orders
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wp_query;

$status_filter = isset($_GET['design_order_status']) ? sanitize_text_field($_GET['design_order_status']) : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Design Orders', 'the-design-order'); ?></h1>
    
    <?php if (isset($_GET['s']) && !empty($_GET['s'])) : ?>
        <span class="subtitle"><?php printf(__('Search results for: %s', 'the-design-order'), '<strong>' . esc_html($_GET['s']) . '</strong>'); ?></span>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ($wp_query->have_posts()) : ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="<?php echo admin_url('edit.php'); ?>">
                    <input type="hidden" name="post_type" value="design_order">
                    
                    <label for="filter-by-status" class="screen-reader-text"><?php _e('Filter by status', 'the-design-order'); ?></label>
                    <select name="design_order_status" id="filter-by-status">
                        <option value=""><?php _e('All statuses', 'the-design-order'); ?></option>
                        <?php
                        $statuses = get_terms([
                            'taxonomy' => 'design_order_status',
                            'hide_empty' => false
                        ]);
                        
                        foreach ($statuses as $status) {
                            echo '<option value="' . esc_attr($status->slug) . '" ' . selected($status_filter, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="submit" class="button" value="<?php _e('Filter', 'the-design-order'); ?>">
                </form>
            </div>
            
            <div class="tablenav-pages">
                <?php
                $pagination = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $wp_query->max_num_pages,
                    'current' => max(1, get_query_var('paged'))
                ]);
                
                if ($pagination) {
                    echo '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $wp_query->found_posts, 'the-design-order'), number_format_i18n($wp_query->found_posts)) . '</span>';
                    echo '<span class="pagination-links">' . $pagination . '</span>';
                }
                ?>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary"><?php _e('Order', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-customer"><?php _e('Customer', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('Status', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php _e('Date', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'the-design-order'); ?></th>
                </tr>
            </thead>
            
            <tbody>
                <?php while ($wp_query->have_posts()) : $wp_query->the_post(); 
                    $order_id = get_the_ID();
                    $name = get_post_meta($order_id, '_design_order_name', true);
                    $email = get_post_meta($order_id, '_design_order_email', true);
                    $status = wp_get_object_terms($order_id, 'design_order_status', ['fields' => 'names']);
                    $status = !empty($status) ? $status[0] : __('Unknown', 'the-design-order');
                ?>
                    <tr>
                        <td class="title column-title has-row-actions column-primary" data-colname="<?php _e('Order', 'the-design-order'); ?>">
                            <strong><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="<?php echo get_edit_post_link(); ?>"><?php _e('Edit', 'the-design-order'); ?></a> | </span>
                                <span class="view"><a href="<?php the_permalink(); ?>" target="_blank"><?php _e('View', 'the-design-order'); ?></a> | </span>
                                <span class="trash"><a href="<?php echo get_delete_post_link(); ?>"><?php _e('Trash', 'the-design-order'); ?></a></span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                        </td>
                        <td class="customer column-customer" data-colname="<?php _e('Customer', 'the-design-order'); ?>">
                            <?php echo esc_html($name); ?>
                            <?php if ($email) : ?>
                                <br><small><?php echo esc_html($email); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="status column-status" data-colname="<?php _e('Status', 'the-design-order'); ?>">
                            <span class="the-design-order-status the-design-order-status-<?php echo sanitize_html_class(strtolower($status)); ?>">
                                <?php echo esc_html($status); ?>
                            </span>
                        </td>
                        <td class="date column-date" data-colname="<?php _e('Date', 'the-design-order'); ?>">
                            <?php echo get_the_date(); ?>
                        </td>
                        <td class="actions column-actions" data-colname="<?php _e('Actions', 'the-design-order'); ?>">
                            <a href="<?php echo get_edit_post_link(); ?>" class="button button-small"><?php _e('Manage', 'the-design-order'); ?></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary"><?php _e('Order', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-customer"><?php _e('Customer', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('Status', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php _e('Date', 'the-design-order'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'the-design-order'); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo $pagination; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="notice notice-warning">
            <p><?php _e('No design orders found.', 'the-design-order'); ?></p>
        </div>
    <?php endif; ?>
</div>