# cloudinary-metaindexer - Note: Work in progress!

Indexes cloudinary account to a mongodb database, and makes it available to list items in the frontend based on configured tags.

This way you can create custom eZ pages that only lists cloudinary resources tagged with certain tags. 

## Prerequisites

- eZ Publish 5.4+ or eZ Platform
- PHP 5.5+
- Mongodb installed on the server. And php extension for mongodb installed.
- A cloudinary account.

## INSTALL


### Step 1

If you are running PHP 5.6+ / 7+ you don't have the old `ext-mongo` installed. Doctrine ODM 1.X requires `ext-mongo`, but there is a adapter available for this case.

You need to install this adapter for mongodb (PHP 5.6+ / 7+):

Note: If you have PHP version less then PHP 5.6 - **skip this step**.

```
composer require alcaeus/mongo-php-adapter --ignore-platform-reqs
```

### Step 2

Install this bundle (stable):

```
composer require keyteq/cloudinary-metaindexer
```



## CONFIGURE

Add to the app kernel to enable the bundle (`app/AppKernel.php`):

```
new Keyteq\Bundle\CloudinaryMetaIndexer\CloudinaryMetaIndexerBundle(),
```

Add standard views configuration to `app/config/ezplatform.yml` / `ezpublish/config/ezpublish.yml`:

```
imports:
    ...
    - resource: '@KeyteqCloudinaryMetaIndexerBundle/Resources/config/ezplatform.yml'
```


Configure api keys and database name for cloudinary in `app/config/config.yml`

In the example below we use environment parameters as values (so you keep the secrets safe).

```
keyteq_cloudinary_meta_indexer:
    cloudinary_api_key: '%cloudinary_key%'
    cloudinary_api_secret: '%cloudinary_secret%'
    cloudinary_cloud_name: '%cloudinary_cloud_name%'
    mongodb:
        server: ~
        database: 'myproject_cloudinary' # Change this to something unique for the project.
```

## Test cloudinary sync

```
php app/console keyteq:cloudinary-meta-indexer:sync
```

## Setup a cron job to run mongodb sync

Setup a new cronjob to run the synchronization job:

NOTE: change `app/console to `ezpublish/console` depending on your eZ publish/platform version.

The below cron specification will run this job at 2:30 every night.

```
30 2 * * * php app/console keyteq:cloudinary-meta-indexer:sync
```


## Create a new cloudinary page in admin

1. Go to the administration interface and import this class: @TODO ( a ez class with tags (text line) .). 
1. Create a new object of the content class. E.g. named "Presse". For tags you can e.g. set "presse". Then this page will only list cloudinary resources with the tag "presse".
1. Visit the page in the browser: http://localhost/Presse . 


## Extending the template with a pagelayout


### Step 1

By default, we don't extend any template, so the layout will be empty ( no header and footer ).

Create your own override in `content_view.yml`:

```
ezpublish:
    system:
        MY_FRONTPAGE_SITEACCESS:
            content_view:
                full:
                    cloudinary_page:
                        controller: keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPage
                        template: "AcmeDemoBundle:content/full:cloudinary_page.html.twig"
                        match:
                            Identifier\ContentType: cloudinary_page
```

Note: If you are using layouts, use "ngcontent_view" instead of content_view.

Note: if you are on eZ Publish 5.X ( not platform), change `viewCloudinaryPage` to `viewCloudinaryPageLocation`.


### Step 2


And create a new template for the cloudinary_page: `AcmeDemoBundle/Resources/views/full/cloudinary_page.html.twig`:

```
{% extends "AcmeDemoBundle::pagelayout.html.twig" %}

{% block content %}
    {% include 'KeyteqCloudinaryMetaIndexerBundle:content/full:cloudinary_page.html.twig' %}
{% endblock %}
```

If you dont want the built in markup you can look inside the `KeyteqCloudinaryMetaIndexerBundle:content/full:cloudinary_page.html.twig`
template and use certain parts of the template for your needs.

