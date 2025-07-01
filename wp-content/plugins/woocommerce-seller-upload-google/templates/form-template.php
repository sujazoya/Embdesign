<?php
/**
 * Template Name: Embroidery Product Upload
 */
get_header();
?>

<div class="emb-form-container">
    <h1>Product Upload</h1>
    <p class="form-description">Submit Your EmbDesign</p>

    <?php echo do_shortcode('[product_submission_form]'); ?>
</div>

<style>
.emb-form-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    box-shadow: 0 0 10px rgba(0,0,0,0.05);
    border-radius: 10px;
}
.emb-form-container h1 {
    font-size: 28px;
    margin-bottom: 10px;
    color: #333;
}
.emb-form-container .form-description {
    font-size: 16px;
    color: #666;
    margin-bottom: 25px;
}
</style>

<?php get_footer(); ?>
