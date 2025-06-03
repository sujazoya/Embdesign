=== Frontend Admin by DynamiApps ===
Contributors: shabti
Tags: frontend editing, frontend posting, acf frontend, custom fields, custom dashboard
Requires at least: 4.6
Tested up to: 6.7.1
Stable tag: 3.28.8
Donate link: https://paypal.me/KaplanWebDev
Requires PHP: 5.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This awesome plugin allows you to easily display frontend forms on your site so your clients can easily edit content by themselves from the frontend.

== Description ==
Add and edit posts, pages, users, terms, ACF fields and more all from the frontend. 

(Previously called ACF Frontend)

This awesome plugin allows you to easily display frontend admin forms on your site so your clients can easily edit content by themselves from the frontend. You can create awesome forms with our form builder to allow users to save custom meta data to pages, posts, users, and more. Then use our Gutenberg block or shortcode to easily display these forms for your users.  

So, what can this plugin do for you?

== FREE Features ==

1. No Coding Required
Give the end user the best content managment experience without having to know code. It’s all ready to go right here. 

2. Display Post Data 
Use [frontend_admin field=field_key] to display any field value effortlessly

3. Edit Posts 
Let your users edit posts from the frontend of their site without having to access the WordPress dashboard. 

4. Add Posts 
Let your users publish new posts from the frontend using the “new post” form

5. Delete Posts 
Let your users delete or trash posts from the frontend using the “trash button” form

6. Edit User Profile
Allow users to edit their user data easily from the frontend.

7. User Registration Form
Allow new users to register to your site with a built in user registration form! You can even hide the WordPress dashboard from these new users.

8. Hide Admin Area 
Pick and chose which users have acess to the WordPress admin area.

9. Configure Permissions
Choose who sees your form based on user role or by specific users.

10. Modal Popup 
Display the form in a modal window that opens when clicking a button so that it won’t take up any space on your pages.


== PRO Features ==

1. Edit Global Options 
If you have global data – like header and footer data – you can create an options page using ACF and let your users edit from the frontend.

2. Limit Submits
Prevent all or specific users from submitting the form more than a number of times.

3. Send Emails 
Set emails to be sent and map the ACF form data to display in the email fields such as the email address, the from address, subject, and message. 

4. Style Tab
Use Elementor to style the form and as well the buttons. 

5. Multi Step Forms 
Make your forms more engaging by adding multiple steps.

6. Stripe and Paypal 
Accept payments through Stripe or Paypal upon form submission. 

7. Woocommerce Intergration 
Easily add Woocomerce products from the frontend.

Purchase your copy here at the official website: [Frontend Admin website](https://www.dynamiapps.com/)


== Intergrations ==

### Page Builders ###
Frontend Admin works with all WordPress page builders, including:

* Elementor
* Bricks Builder
* Spectra Website Builder
* Divi Builder
* Beaver Builder
* Thrive Architect
* Gutenberg
* Oxygen Builder
* And others

### Other Plugins ###
Frontend Admin has built-in integrations with very popular plugins, such as:

* WooCommmerce
* Easy Digital Downloads
* SureCart
* Advanced Custom Fields
* Pods
* And more
 


== Useful Links ==
Appreciate what we're doing? Want to stay updated with new features? Give us a like and follow us on our facebook page: 
[Frontend Admin Facebook page](https://www.facebook.com/frontendadmin/)

The Pro version has even more cool features. Check it out at the official website:
[DynamiApps website](https://www.dynamiapps.com/)

Check out our other plugin, which let's you dynamically query your posts more easily: 
[Advanced Post Queries for Elementor](https://wordpress.org/plugins/advanced-post-queries/)


== Installation ==

1. Make sure both Advanced Custom Fields is installed and activated. 
2. Upload the plugin files to the `/wp-content/plugins/acf-frontend-form-elements` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Create a form under Frontend Admin > forms.
5. Choose the desired form type. 
6. Configure the fields permisions, display, and other settings as you please.
7. Copy and paste the shortcode on any page. You can also use our Gutenberg block.
8. You should now see a form on the frontend for editing a post, adding a post, editing or adding a user, and more.



== Tutorials ==  

= Paul from WP Tuts shows how to use Frontend Admin to create a frontend dashboard =
https://www.youtube.com/watch?v=FsLSrd-11-g

= Bjorn from WPLearningLab shows how to create a WordPress Client Portal =
https://www.youtube.com/watch?v=yjhd8GPi28o

= Frontend Forms in Elementor Pro Off Canvas Widget =
https://www.youtube.com/watch?v=UII4uwpMP0I

= Frontend Admin's Elementor Nestable Forms Widget on Wordpress =
https://www.youtube.com/watch?v=gxN5X54LNuk&t=2s

= Enable Users To Add Content From The Front End Without Logging Into WordPress Using Frontend Admin =
https://www.youtube.com/watch?v=6yT2E2IV-JU

= WordPress Frontend Edits and Updates Using Frontend Admin =
https://www.youtube.com/watch?v=s6FeL77i2iM

= Installating Frontend Admin =
https://www.youtube.com/watch?v=Qio9iHzpMLo

= How to create a form for frontend data submission =
https://www.youtube.com/watch?v=7vrW8hx5jlE



== Frequently Asked Questions ==

= Can I send emails through this form? =

You can use a "action hook" to send emails when this form is registered: <a href="https://www.dynamiapps.com/frontend_admin/save_post/">frontend_admin/save_post</a>

If you purchase our pro version, you will be able to configure this from the form settings without any code. You will be able to send any number of emails upon form submission. 

= Can I let users set post categories through this form? =

Yes. Simply add a taxonomy field and set the taxonomy type to Category




== Changelog ==
= 3.28.8 - 27-05-2025 =
 * Fixed 'fea-upload-file' function to show hidden files to 
 * Fixed post to edit "new post" feature not saving to correct post type
 * Fixed submissions page igonoring current page parameter

= 3.28.7 - 21-05-2025 =
 * Added option to show CSV download option on users page
 * Fixed files showing in PDF

= 3.28.6 - 19-05-2025 =
 * Fixed issue with "all_fields" in email and pdf

= 3.28.5 - 19-05-2025 =
 * Fixed "orderby" issue
 * Fixed gallery field issue
 
= 3.28.3 - 11-05-2025 =
 * Fixed email "all fields" not showing properly 

= 3.28.1 - 07-05-2025 =
 * Fixed error with _() function
 
= 3.28.0 - 04-05-2025 =
 * Added Bricks Elements to easily and intuitivly build frontend dashboards with Bricks 

= 3.27.2 - 03-04-2025 =
 * Added post excerpt block
 * Added wp_update_user hook

= 3.27.1 - 27-03-2025 =
 * Added Lightbox to gallery field
 * Fixed frontend form Gutenberg blocks
 * Added Custom post type variations for New Post Form block
 * Fixed broken conditions in repeater fields
 * Fixed User to Edit field search function

= 3.26.6 - 22-03-2025 =
 * Fixed conflict with Gutenberg Button Block

= 3.26.5 - 21-03-2025 =
 * Fixed Free and Pro versions conflicting
 * Fixed Nonce errors to say "Authentication Error. Please try refreshing the page."
 * Added "default_terms" setting to taxonomy field

= 3.26.4 - 18-03-2025 =
 * Fixed user email and password not saving in edit user form
 * Fixed color input field warning
 * Removed Google maps secret key setting which is no longer required 

= 3.26.3 - 13-03-2025 =
 * Added User To Edit field

= 3.26.2 - 07-03-2025 =
 * Added option to pay to publish using Easy Digital Downloads
 * Fixed Exclude typo
 * Fixed submissions not loading post and user data

= 3.26.1 - 04-03-2025 =
 * Added Elementor widget conditions

= 3.26.0 - 03-03-2025 =
 * Fixed non translatble strings
 * Fixed Chceckout items error
 * Added Freemius to free version 

= 3.25.22 - 21-02-2025 =
 * Fixed submissions not saving product data after Woo Checkout
 * Fixed conditional logic in Elementor widgets
 * Added Dynamic Values dropdown to textarea field settings.  

= 3.25.21 - 19-02-2025 =
 * Moved main validation error to show after form
 * Changed validation to scroll to first error message rather than to the top of the form
 * Fixed product form submissions not loading values

= 3.25.20 - 17-02-2025 =
 * Fixed OR logic in conditional rules

= 3.25.19 - 12-02-2025 =
 * Added Completed Step style in multi step form
 * Fixed conditional logic when fields are in two seperate widgets

= 3.25.18 - 09-02-2025 =
 * Fixed image upload preview element to update <source>

= 3.25.17 - 09-02-2025 =
 * Added hook for multi step buttons
 * Fixed bug on admin submissions page

= 3.25.15 - 04-02-2025 =
 * Fixed mmissing step tab text setting

= 3.25.14 - 02-02-2025 =
 * Fixed multi step in Elementor widgets

= 3.25.13 - 28-01-2025 =
 * Added automatic redirect to checkout 

= 3.25.12 - 28-01-2025 =
 * Added Checkout action to forms
 * Fixed issue with user fields
 * Fixed Elementor error when Nested Elements feature is not active

= 3.25.11 - 26-01-2025 =
 * Fixed conflict with ACF validation
 * Temporarily removed Blocks Editor field because of Gutenberg instability 

= 3.25.10 - 22-01-2025 =
 * Fixed missing formatting in long text shortcodes

= 3.25.8 - 16-01-2025 =
 * Added option to Upload Files field to download uploads when clicked
 * Fixed Custom Directory Folder feature
 * Fixed Secure Directory feature and moved it to the pro version
 * Fixed Attributes and Variations fields in Elementor widgets
 * Added Nestable Add Product Form (pro feature) 

= 3.25.7 - 07-01-2025 =
 * Fixed upload files field
 * Fixed new file not saving meta
 * Added option to disable users from adding new attribute terms

= 3.25.5 - 07-01-2025 =
 * Fixed edit post form not showing when no post types selected
 * Fixed Elementor padding and margin controls repeating
 * Added option to show Product Attributes as Dropdowns
 * Added option to hide Visibilty Field in Product Attributes and to enable visibilty by default
 * Added option to exclude the custom attributes from Attributes field

= 3.25.4 - 31-12-2024 =
 * Fixed conflict with ACF gallery field
 * Updated Freemius sdk for pro version

= 3.25.3 - 18-12-2024 =
 * Fixed conflict with Elementor 3.26 

= 3.25.2 - 17-12-2024 =
 * Fixed orderby error
 * Fixed file meta not saving
 * Fixed permisions not working

= 3.25.1 - 06-12-2024 =
 * Fixed Delete Post widget for Elementor not rendering
 * Fixed role field issue
 * Fixed submission title bug
 * Fixed orderBy bug

= 3.25.0 - 03-12-2024 =
 * Changed permissions to show to all roles if none are selected. 
 * Fixing permisions message showing after permissions are configured
 * Fixed Woocomerce variations and atributes fields

= 3.24.8 - 12-11-2024 =
 * Fixed product inventory fields not saving

= 3.24.7 - 5-11-2024 =
 * Fixed "From Email" setting not saving
 * Fixed multi step validation
 * Added dynamic value checks to custom url redirect setting 

= 3.24.6 - 4-11-2024 =
 * Fixed multi step form not proceding to next step

= 3.24.5 - 3-11-2024 =
 * Fixed conflict with ACF update causing multi select fields to show no results
 * Added taxonomy field widget in Elementor

= 3.24.4 - 1-11-2024 =
 * Fixed ACF image fields adding suffix
 * Added "ACF Fields" button in form builder
 * Fixed "count error"
 * Added option to hide modal button if there is no post being edited

= 3.24.1 - 07-10-2024 =
 * Removed ACF dependancy. Still works seamlessly with ACF, but doesn't require it to be activated

= 3.23.9 - 26-09-2024 =
 * Fixed passwrod field not allowing very weak passwords despite the setting
 * Fixed password preview showing even when password field is hidden
 * Fixed Submissions not loading post fields data

= 3.23.8 - 24-09-2024 =
 * Added form_element to form response
 
 = 3.23.7 - 23-09-2024 =
 * Fixed gallery field layout

= 3.23.6 - 22-09-2024 =
 * Added encryption for extra security for inline edit fields
 * Added support for terms in loops

= 3.23.4 - 18-09-2024 =
 * Patched missing function
 
 = 3.23.3 - 18-09-2024 =
 * Added Frontend Admin metabox to enable "visibilty" limitation based on user role
 * Added compatibility for Elementor's element cache feature

= 3.23.2 - 17-09-2024 =
 * Improved ACF fields selections in the Elementor editor
 * Fixed delete button not showing up

= 3.23.1 - 10-09-2024 =
 * Fixed delete button not working within nested form
 * Fixed submissions table error

= 3.23.0 - 10-09-2024 =
 * Added Nested "New Taxonomy Form" and Nested "Edit Taxonomy Form
 * Added support for Elementor taxonomy loops
 * Fixed Image validation issue
 * Fixed delete button not redirecting

= 3.22.4 - 01-09-2024 =
 * Fixed edit permissions issue

= 3.22.3 - 15-08-2024 =
 * Fixed file upload error

= 3.22.2 - 14-08-2024 =
 * Fixed Google Map field not initializing
 * Added option to add password revealer

= 3.22.1 - 09-08-2024 =
 * Fixed field info not saving
 * Added option to add a preview via the 'frontend_admin/form/submission_preview' filter hook

= 3.22.0 - 12-07-2024 =
 * Added support for PHP 8.2
 * Added redirect options to submit buttons
 * Fixed edit term form
 * Fixed media library not working with latest WP version
 * Added proper filename sanitization 

= 3.21.12 - 27-06-2024 =
 * Fixed select2 fields (taxonomy, post author, select) not fetching options
 * Fixed Elementor Manage Product widgets not showing fields
 * Fixed 'special permissions' reference error

= 3.21.10 - 25-06-2024 =
 * Fixed browser autofill triggering form input change
 * Fixed default required message

= 3.21.9 - 23-06-2024 =
 * Fixed Elementor widget permissions
 * Added limit submissions to permissions rule

= 3.21.8 - 18-06-2024 =
 * Fixed "New post terms" option not working
 * Added option Special Permissions to allow users to edit other posts and users if checked

= 3.21.7 - 13-06-2024 =
 * Fixed product delete button not working when together with "acf form" widget
 * Fixed acf nested fields not validating
 * Added ability to add shortcodes to emails and to the message field

= 3.21.6 - 09-06-2024 =
 * Fixed delete button not working

= 3.21.5 - 07-06-2024 =
 * Fixed success message always showing in "fixed" position
 * Fixed form validation not calling acf/validate_save_post hook
 * Fixed email verification feature to allow non logged in users to post content by verifying email address
 * Added fea_emails db table for handling verified emails 

= 3.21.4 - 05-06-2024 =
 * Added option to add any string to the file input allowed attribute

= 3.21.3 - 03-06-2024 =
 * Fixed new posts not being creating upon submission approval
 * Fixed delete button missing dependencies when displayed alone
 * Fixed file field not accepting mp4

= 3.21.1 - 31-05-2024 =
 * Fixed submission approval not working
 * Added extra context to forms so that success messages will only show based on context if displayed in loop
 * Added option to click enter to go to the next field

= 3.21.0 - 28-05-2024 =
 * Added ajax submit feature to free version
 * Improved delete button html
 * Added logic to check if post exists before attempting to render edit post form and delete button 
 * Added logic to check if user exists before attempting to render edit user form and delete button 
 * Added option to customize redirect based on which submit button is triggered

= 3.20.16 - 24-05-2024 =
 * Added SQLlite support

= 3.20.15 - 23-05-2024 =
 * Fixed Post to Edit field loading form as modal within modal
 * Fixed Post to Edit field adding new posts to posts instead of CPT

= 3.20.14 - 22-05-2024 =
 * Optimized file uploads speed
 * Improved uploads progress bar
 * Added warnings when uploads exceed server upload limits
 * Added option to either add validation warning or client-side resize the images
 * Added "local avatar" option directly inside of image fields, simplifying the process of creating a local avatar field

= 3.20.12 - 17-05-2024 =
 * Fixed modal window in form builder
 * Fixed verifiy emails
 * Added email verified success message

= 3.20.11 - 15-05-2024 =
 * Fixed url query not working in Elementor built forms
 * Added duplicate post "copy title" option
 * Added duplicate post "copy date" option
 * Added "new post status" option for duplicate post

= 3.20.9 - 15-05-2024 =
 * Fixed approval forms not working
 * Fixed relationship field within repeater or group not using the correct form or field groups for inner forms
 * Fixed date picker fields display error

= 3.20.8 - 11-05-2024 =
 * Fixed WP library not working
 * Fixed post_to_edit showing form "post" on initial load 

= 3.20.7 - 10-05-2024 =
 * Fixed delete button not working
 * Fixed File field not opening WP media library

= 3.20.5 - 08-05-2024 =
 * Fixed show in modal not working in Admin Forms
 * Fixed field shortcodes not updating data

= 3.20.4 - 07-05-2024 =
 * Fixed missing block editor field

= 3.20.3 - 07-05-2024 =
 * Fixed "Upload Images" field mobile display
 * Fixed ios upload file error
 * Added Elementor "Images Field" widget
 * Fixed Nested forms basic upload not working
 * Added inline edit to fields widgets
 * Fixed date field shortcodes so that they display based on the return format

= 3.20.1 - 03-05-2024 =
 * Fixed Paypal not working
 * Change "form" hidden field from serialzed json to a form id



== Upgrade Notice ==





