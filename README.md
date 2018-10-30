# Reef: Responsive Embeddable Extensible Form generator

### Reef is a form generator written in PHP. It provides functionality to let your users build forms that can in turn be filled in by other users.

[Home page](https://reefphp.gitlab.io/home/) - [Packagist](https://packagist.org/packages/reef/reef)

[User's guide](https://reef-docs.readthedocs.io/en/latest/users_guide/) - [Components reference](https://reef-docs.readthedocs.io/en/latest/components/) - [Integration guide](https://reef-docs.readthedocs.io/en/latest/integration_guide/) - [Contribution guide](https://reef-docs.readthedocs.io/en/latest/contribution_guide/)

[![Build Status](https://scrutinizer-ci.com/g/reefphp/reef/badges/build.png?b=master)](https://scrutinizer-ci.com/g/reefphp/reef/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/reefphp/reef/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/reefphp/reef/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/reefphp/reef/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/reefphp/reef/?branch=master)

## Features

* Creating, modifying and deleting forms. Forms can also be modified after submissions have been received
* Filling in forms, editing and deleting these submissions. Submissions are automatically validated in javascript, as well as on the server-side
* Extensible in every way imaginable: add third-party languages, layouts, form components, extensions or icon sets, or create your own
* It is framework agnostic, in the sense that Reef is not built on top of or using a PHP framework. Hence, it can be used in combination with any framework! Reef only requires a small number of PHP and Javascript libraries.

Reef can be embedded seamlessly into your own website. Reef only provides the form functionality: authentication and authorization are up to you. This is regarded as a feature: generally you do not want a separate login on your website for accessing the form functionality!

## Notable dependencies

* jQuery
* Mustache.php and Mustache.js for templates shared between PHP and JS (Mustache.js only in builder)
* RubaXa/Sortable JS library (only in builder)
