<div class="wrap suggestion-box-admin">
    <h1>Suggestions Management</h1>
    
    <div class="suggestion-tabs">
        <h2 class="nav-tab-wrapper">
            <a href="#pending" class="nav-tab nav-tab-active">Pending</a>
            <a href="#approved" class="nav-tab">Approved</a>
            <a href="#rejected" class="nav-tab">Rejected</a>
        </h2>
    </div>
    
    <div id="pending" class="suggestion-tab-content active">
        <h2>Pending Suggestions</h2>
        <?php if (!empty($pending)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Suggestion</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item['name']); ?></td>
                        <td><?php echo esc_html($item['email']); ?></td>
                        <td><?php echo esc_html($item['suggestion']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                        <td>
                            <button class="button button-primary approve-btn" data-id="<?php echo $item['id']; ?>">Approve</button>
                            <button class="button button-secondary reject-btn" data-id="<?php echo $item['id']; ?>">Reject</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending suggestions.</p>
        <?php endif; ?>
    </div>
    
    <div id="approved" class="suggestion-tab-content">
        <h2>Approved Suggestions</h2>
        <?php if (!empty($approved)): ?>
            <table class="wp-list-table widefat fixed striped">
                <!-- Similar structure as pending table -->
            </table>
        <?php else: ?>
            <p>No approved suggestions.</p>
        <?php endif; ?>
    </div>
    
    <div id="rejected" class="suggestion-tab-content">
        <h2>Rejected Suggestions</h2>
        <?php if (!empty($rejected)): ?>
            <table class="wp-list-table widefat fixed striped">
                <!-- Similar structure as pending table -->
            </table>
        <?php else: ?>
            <p>No rejected suggestions.</p>
        <?php endif; ?>
    </div>
</div>