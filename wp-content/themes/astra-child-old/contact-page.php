<?php
/**
 * Template Name: Contact Page
 */
get_header(); ?>

<div class="contact-page-container">
    <div class="contact-form-section">
        <h1><?php the_title(); ?></h1>
        
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <div class="contact-content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; endif; ?>
        
        <div class="contact-form-wrapper">
            <?php echo do_shortcode('[contact-form-7 id="123" title="Contact form"]'); ?>
        </div>
    </div>
    
    <div class="contact-info-section">
        <div class="contact-info-card">
            <h3><i class="fas fa-map-marker-alt"></i> Our Location</h3>
            <p>123 Business Street<br>City, State 10001<br>Country</p>
        </div>
        
        <div class="contact-info-card">
            <h3><i class="fas fa-phone"></i> Call Us</h3>
            <p>+1 (123) 456-7890<br>+1 (987) 654-3210</p>
        </div>
        
        <div class="contact-info-card">
            <h3><i class="fas fa-envelope"></i> Email Us</h3>
            <p>info@yourbusiness.com<br>support@yourbusiness.com</p>
        </div>
        
        <div class="contact-info-card">
            <h3><i class="fas fa-clock"></i> Business Hours</h3>
            <p>Monday-Friday: 9am-6pm<br>Saturday: 10am-4pm<br>Sunday: Closed</p>
        </div>
    </div>
    
    <div class="contact-map-section">
        <iframe src=https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d227.2454464175094!2d88.09371324118874!3d24.314176313567362!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sin!4v1748506445111!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy"></iframe>
    </div>
</div>

<?php get_footer(); ?>