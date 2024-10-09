# Gravity Forms Global Forms
Allows forms from the Gravity Forms plugin to be used across any site on a WordPress multisite installation via a new shortcode.

## Requirements
* WordPress multisite
* Gravity Forms plugin must be installed and activated (on all sites where a global form will be used)

## Installation
1. Download the gravity-forms-global-forms.php file.
2. Upload the gravity-forms-global-forms.php file to the wp-content/plugins/gravity-forms-global-forms/ directory on your web server.
3. Log in to WordPress and go to Plugins and Activate the Gravity Forms Global Forms plugin.

## Usage
Create a form on a site within your WordPress multisite instance and then use the `[gravityform_global /]` to embed the form on any site within the same WordPress multisite instance. Use the shortcode the same way as the Gravity Forms `[gravityform /]` is used, with the addition of the following attributes:
* **form_url**
  * Description: The page url where the original form has been embedded on the site where the form was created.
  * Type: string
  * Value: Optional (as long as `site_domain` or `site_id` are set)
  * Default: null
* **redirect_to_origin**
  * Description: Whether to submit the form to the current/remote page where the form is embedded (false) or submit the form to the 'form_url' where the original form exists (true)
  * Type: boolean
  * Value: Optional
  * Default: false
* **site_domain**
  * Description: The domain of the site where the form was created (only needed if 'form_url' isn't set). This may be used in cases that the form was not embedded on the site where it was created.
  * Type: string
  * Value: Optional
  * Default: null
* **site_id**
  * Description: The site ID of the site where the form exists (only needed if 'form_url' and 'site_domain' aren't set). This may be used in cases where the domain may change.
  * Type: int|string
  * Value: Optional
  * Default: null
 
### Example
`[gravityform_global id="25" form_url="https://www.example.com/contact/" title="false" /]`
* **id** is the Gravity Forms form ID (25 in this example) that can be found when editing the form.
* **form_url** is the page on the example.com website where the form has already been embedded.
