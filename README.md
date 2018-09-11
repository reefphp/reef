# Reef: Responsive Embeddable Extensible Form generator

### Reef is a form generator written in PHP. It provides functionality to let your users build forms that can in turn be filled in by other users.

## Features

* Creating, modifying and deleting forms. Forms can also be modified after submissions have been received
* Filling in forms, editing and deleting these submissions. Submissions are automatically validated in javascript, as well as on the server-side
* Extensible in every way imaginable: add third-party languages, layouts, form components, extensions or icon sets, or create your own

Reef can be embedded seamlessly into your own website. Reef only provides the form functionality: authentication and authorization are up to you. This is regarded as a feature: generally you do not want a separate login on your website for accessing the form functionality!

## Notable dependencies

* Mustache.php and Mustache.js for templates shared between PHP and JS
* RubaXa/Sortable JS library
