# cloudinary-metaindexer

Indexes cloudinary account to a mongodb database, and makes it available to list items in the frontend based on configured tags AND/OR a folder prefix.

This way you can create custom eZ pages that only lists cloudinary resources tagged with certain tags or within a cloudinary folder.

## Prerequisites

- eZ Publish 5.4+ or eZ Platform
- PHP 5.5+
- Mongodb installed on the server. And php extension for mongodb installed.
- A cloudinary account.
- A cron job (see doc below)

# INSTALL


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

### Step 3 - (if you have doctrine mongodb installed from before)

If you have configured doctrine mongodb in your project for some other tasks you will need to disable the auto mapping for the cloudianry meta indexer bundle. That is ONLY if you have auto_mapping set to true.

Update your `config.yml` where `doctrine_mongodb` is configured:
```

doctrine_mongodb:
    #... 
    document_managers:
        default:
            auto_mapping: true
            mappings:
                # Disable mapping for cloudinary meta indexer bundle, it has its own document manager.
                KeyteqCloudinaryMetaIndexerBundle:
                    mapping: false

```

# CONFIGURE

Add to the app kernel to enable the bundle (`app/AppKernel.php`):

```
new Keyteq\Bundle\CloudinaryMetaIndexer\KeyteqCloudinaryMetaIndexerBundle(),
```

Add required configuration (remember to change the database to something unique for your project).

app/config/config.yml:
```
keyteq_cloudinary_meta_indexer:
    cloudinary_api_key: '%cloudinary_key%'
    cloudinary_api_secret: '%cloudinary_secret%'
    cloudinary_cloud_name: '%cloudinary_cloud_name%'
    mongodb:
        server: ~
        database: 'myproject_cloudinary' # Change this to something unique for the project.
```

And update parameters.yml.dist:

```
parameters:
    # ....
    cloudinary_cloud_name: ~
    cloudinary_key: ~
    cloudinary_secret: ~
    # ....
```

Run composer install to add your secret parameters.

```
composer install
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


## Import the cloudinary_page package


See `Resources/ezcontentclass/` folder in this bundle, there is a content class that you can import via ez administration ui. 
That package includes a new content class named `cloudinary_page` and contains some mandatory attributes. You are free to add 
your own after you import the content class.

After imported, create a new content object of that specific class.


# Extending the template with a pagelayout (required for 5.x of eZ)

Step 1 is required for 5.X of ezpublish.


### Step 1

By default, we don't extend any template, so the layout will be empty ( no header and footer ).

- Note: For 5.x use controller `keyteq.cloudinary_meta_indexer.controller.full_view:viewCloudinaryPageLocation`.
- Note: If you are using layouts, use "ngcontent_view" instead of content_view.

Create your own override in `yourezbundleconfig.yml`:

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



#### Customization of controller

#### Changing default 12 resources per page.

To set 24 resources per page for siteaccess `YOUR_FRONT_SITEACCESS`:

parameters.yml:

```
parameters:
    ezsettings.YOUR_FRONT_SITEACCESS.cloudinary_meta_indexer.resources_per_page: 24
```


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


