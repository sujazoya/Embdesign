<div class="emb-form-container">
    <h1>Product Upload</h1>
    <p class="form-description">Submit Your EmbDesign</p>
    // Add this where your file upload field is located
echo '<div class="wcsu-upload-field">';
echo '<label for="wcsu-gallery">' . __('Design Pictures (JPG only, max 500KB)', 'your-plugin-textdomain') . '</label>';
echo '<input type="file" id="wcsu-gallery" name="gallery[]" accept="image/jpeg" multiple>';
echo '<div id="wcsu-file-errors" class="wcsu-upload-errors" style="display:none;"></div>';
echo '</div>';
    
    <form method="post" enctype="multipart/form-data">
        <!-- Product Details -->
        <div class="form-section">
            <div class="form-group">
                <label>Product Code</label>
                <input type="text" name="product_code" value="C433">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" value="63000">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" value="102">
                </div>
            </div>
        </div>

        <!-- File Uploads -->
        <div class="form-section">
            <div class="form-group">
                <label>Gallery Image</label>
                <input type="file" name="gallery_image">
            </div>
            
            <div class="form-group">
                <label>DST File</label>
                <input type="file" name="dst_file">
            </div>
        </div>

        <!-- Category -->
        <div class="form-group">
            <label>Select Category</label>
            <select name="category">
                <option value="Necklace">Necklace</option>
                <option value="Bracelet">Bracelet</option>
            </select>
        </div>

        <button type="submit" name="submit_product" class="submit-btn">Submit Product</button>
    </form>
</div>