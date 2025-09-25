<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Partners', 'roanga-partner'); ?></h1>
    
    <!-- Filtry podle skupin -->
    <div class="rpp-filters-section">
        <div class="rpp-filter-group">
            <label for="group-filter"><?php _e('Skupina:', 'roanga-partner'); ?></label>
            <select name="group" id="group-filter">
                <option value=""><?php _e('Všechny skupiny', 'roanga-partner'); ?></option>
                <?php
                $groups_class = new RPP_Partner_Groups();
                $groups = $groups_class->get_all_groups();
                foreach ($groups as $group):
                ?>
                    <option value="<?php echo $group->id; ?>" <?php selected($group_filter ?? '', $group->id); ?>>
                        <?php echo esc_html($group->name); ?> (<?php echo $group->commission_rate; ?>%)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="status" id="status-filter">
                <option value=""><?php _e('All statuses', 'roanga-partner'); ?></option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'roanga-partner'); ?></option>
                <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php _e('Approved', 'roanga-partner'); ?></option>
                <option value="rejected" <?php selected($status_filter, 'rejected'); ?>><?php _e('Rejected', 'roanga-partner'); ?></option>
            </select>
            
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search partners...', 'roanga-partner'); ?>">
            <input type="submit" name="filter_action" class="button" value="<?php _e('Filter', 'roanga-partner'); ?>">
        </div>
        
        <div class="tablenav-pages">
            <?php
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page
            ));
            if ($page_links) {
                echo '<span class="displaying-num">' . sprintf(_n('%d item', '%d items', $total_partners), $total_partners) . '</span>';
                echo $page_links;
            }
            ?>
        </div>
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Partner', 'roanga-partner'); ?></th>
                <th><?php _e('Email', 'roanga-partner'); ?></th>
                <th><?php _e('Partner Code', 'roanga-partner'); ?></th>
                <th><?php _e('Skupina', 'roanga-partner'); ?></th>
                <th><?php _e('Status', 'roanga-partner'); ?></th>
                <th><?php _e('Commission Rate', 'roanga-partner'); ?></th>
                <th><?php _e('Total Earnings', 'roanga-partner'); ?></th>
                <th><?php _e('Referrals', 'roanga-partner'); ?></th>
                <th><?php _e('Registered', 'roanga-partner'); ?></th>
                <th><?php _e('Actions', 'roanga-partner'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($partners)): ?>
                <tr>
                    <td colspan="10"><?php _e('No partners found.', 'roanga-partner'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($partners as $partner): ?>
                    <tr>
                        <td><strong><?php echo esc_html($partner->display_name); ?></strong></td>
                        <td><?php echo esc_html($partner->user_email); ?></td>
                        <td><code><?php echo esc_html($partner->partner_code); ?></code></td>
                        <td>
                            <?php 
                            $group = $groups_class->get_group($partner->group_id ?? 1);
                            echo $group ? esc_html($group->name) : 'Standardní';
                            ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($partner->status); ?>">
                                <?php echo esc_html(ucfirst($partner->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($partner->commission_rate); ?>%</td>
                        <td><?php echo wc_price($partner->total_earnings); ?></td>
                        <td><?php echo intval($partner->total_referrals); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($partner->created_at)); ?></td>
                        <td>
                            <?php if ($partner->status === 'pending'): ?>
                                <button class="button button-small approve-partner" data-partner-id="<?php echo $partner->id; ?>">
                                    <?php _e('Approve', 'roanga-partner'); ?>
                                </button>
                                <button class="button button-small reject-partner" data-partner-id="<?php echo $partner->id; ?>">
                                    <?php _e('Reject', 'roanga-partner'); ?>
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo admin_url('admin.php?page=rpp-partner-detail&id=' . $partner->id); ?>" class="button button-small">
                                <?php _e('View Details', 'roanga-partner'); ?>
                            </a>
                            <button class="button button-small rpp-view-tree" data-partner-id="<?php echo $partner->id; ?>">
                                <?php _e('MLM Strom', 'roanga-partner'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MLM Tree Modal -->
<div id="mlm-tree-modal" class="rpp-modal" style="display: none;">
    <div class="rpp-modal-overlay"></div>
    <div class="rpp-modal-content rpp-modal-large">
        <div class="rpp-modal-header">
            <h2 id="tree-modal-title"><?php _e('MLM Strom partnera', 'roanga-partner'); ?></h2>
            <button class="rpp-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="rpp-modal-body">
            <div id="mlm-tree-content">
                <!-- Tree content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.rpp-filters-section {
    background: white;
    padding: 16px;
    margin: 16px 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.rpp-filter-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rpp-filter-group label {
    font-weight: 600;
    color: #2d5a27;
}

.rpp-filter-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 200px;
}

.status-badge {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-pending { background: #fef2c0; color: #8a6914; }
.status-approved { background: #c8e6c9; color: #2e7d32; }
.status-rejected { background: #ffcdd2; color: #c62828; }

.rpp-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rpp-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.rpp-modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.rpp-modal-large {
    max-width: 1200px;
}

.rpp-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid #e8f5e8;
    background: linear-gradient(135deg, #f8f9fa 0%, #e8f5e8 100%);
}

.rpp-modal-header h2 {
    margin: 0;
    color: #2d5a27;
    font-size: 24px;
    font-weight: 600;
}

.rpp-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.rpp-modal-close:hover {
    background: #ffebee;
    color: #c62828;
}

.rpp-modal-body {
    padding: 24px;
}

.rpp-tree-node {
    background: white;
    border: 2px solid #e8f5e8;
    border-radius: 12px;
    padding: 16px;
    margin: 8px;
    text-align: center;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.rpp-tree-node:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.rpp-tree-node.level-1 { border-color: #d4af37; }
.rpp-tree-node.level-2 { border-color: #4a7c59; }
.rpp-tree-node.level-3 { border-color: #2d5a27; }

.rpp-node-name {
    font-weight: 600;
    color: #2d5a27;
    margin-bottom: 4px;
}

.rpp-node-code {
    font-family: monospace;
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
}

.rpp-node-earnings {
    font-weight: 700;
    color: #d4af37;
    font-size: 14px;
}

.rpp-tree-level {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    margin: 20px 0;
}

.rpp-tree-connector {
    width: 2px;
    height: 30px;
    background: #e8f5e8;
    margin: 0 auto;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Group filter
    $('#group-filter').on('change', function() {
        var group = $(this).val();
        var url = new URL(window.location.href);
        
        if (group) {
            url.searchParams.set('group', group);
        } else {
            url.searchParams.delete('group');
        }
        
        url.searchParams.delete('paged');
        window.location.href = url.toString();
    });
    
    // MLM Tree view
    $('.rpp-view-tree').on('click', function() {
        var partnerId = $(this).data('partner-id');
        var modal = $('#mlm-tree-modal');
        var content = $('#mlm-tree-content');
        
        content.html('<div style="text-align: center; padding: 40px;"><span class="spinner is-active"></span> Načítám MLM strom...</div>');
        modal.show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rpp_get_mlm_tree',
                partner_id: partnerId,
                nonce: rpp_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    content.html(response.data);
                } else {
                    content.html('<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při načítání stromu: ' + response.data + '</div>');
                }
            },
            error: function() {
                content.html('<div style="text-align: center; padding: 40px; color: #c62828;">Chyba při komunikaci se serverem.</div>');
            }
        });
    });
    
    // Close modal
    $('.rpp-modal-close, .rpp-modal-overlay').on('click', function() {
        $('#mlm-tree-modal').hide();
    });
});
</script>