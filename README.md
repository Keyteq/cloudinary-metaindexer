# cloudinary-metaindexer
Indexes cloudinary account to a mongodb database, and makes it available to list items in the frontend based on selected tags.

## INSTALL

If you are running PHP 7+ you don't have the old legacy mongodb ext. and need to install this adapter 

```
composer require alcaeus/mongo-php-adapter --ignore-platform-reqs
```

Then, install this bundle (stable):

```
composer require keyteq/cloudinary-metaindexer
```

To use master branch:

composer.json

```
    "require" : {
        [...]
        "keyteq/cloudinary-metaindexer" : "dev-master"
    },
    "repositories" : [{
        "type" : "vcs",
        "url" : "https://github.com/keyteq/cloudinary-metaindexer.git"
    }],

```

Add to the app kernel to enable the bundle:

app/AppKernel.php

```
new Keyteq\Bundle\CloudinaryMetaIndexer\CloudinaryMetaIndexerBundle(),
```


Configure api keys for cloudinary

```
keyteq_cloudinary_meta_indexer:
    cloudinary_api_key: 'xxxxxxx'
    cloudinary_api_secret: 'yyyyyyyy'
    cloudinary_cloud_name: 'zzzz'
```