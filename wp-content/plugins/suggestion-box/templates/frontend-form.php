<div class="suggestion-box-container">
    <div class="suggestion-box-header">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <p><?php echo esc_html($atts['description']); ?></p>
    </div>
    
    <form id="suggestion-form" class="suggestion-box-form">
        <div class="form-group">
            <label for="suggestion-name">Your Name</label>
            <input type="text" id="suggestion-name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="suggestion-email">Your Email</label>
            <input type="email" id="suggestion-email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="suggestion-text">Your Suggestion</label>
            <textarea id="suggestion-text" name="suggestion" rows="5" required></textarea>
        </div>
        
        <div class="form-group">
            <button type="submit" class="suggestion-submit-btn">Submit Suggestion</button>
        </div>
        
        <div id="suggestion-response" class="suggestion-response"></div>
    </form>
</div>