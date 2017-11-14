# cloudinary-metaindexer
Indexes cloudinary account to a mongodb database, and makes it available to list items in the frontend based on selected tags.

## INSTALL

If you are running PHP 7+ you don't have the old legacy mongodb ext. and need to install this adapter 

```
composer require alcaeus/mongo-php-adapter --ignore-platform-reqs
```

Then, install this bundle:

```
composer require keyteq/cloudinary-metaindexer
```
