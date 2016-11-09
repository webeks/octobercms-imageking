# ImageKing by Code200 for OctoberCMS

##Description
This plugin provides a middleware that automatic resizes images to arbitary max width, generates responsive dimensions, adds watermark and captions to all locally served images in your html. 

Everything is done on the fly allowing you flexibility but at the same time be careful since it will add additional loads on your server (see todo section).

All image copies are saved in your public temp path. Remote file systems are currently untested.

##How it works
When request is made plugin check for local images in response HTML. All found images are processed based on settings and then HTML with replaced image paths is sent to the client. 

##Configuration
Go to ***Settings*** -> ***System*** -> ***Image King*** and edit appropriate settings under all tabs.

####Exclude certain image from processing
You can define exclude class in settings. If you would use default _noProcess_ class then in your blog you could use
```
![1](/storage/app/media/example.png "this is title that could be used as caption"){.noProcess}
```

##Bug report
It is very likely that there will be bugs with some specific html markup. If you encounter such a bug, please report it.

##Future plans
* only generate new image if it doesnt exist in temporary folder already
* special button under settings allowing to purge temporary folder
* generation of appropriate HTML on blog post save instead on client request
* special button under settings allowing to regenerate all HMTL from blog posts

##Attributions
* Inspiration and part of the code is based on [ResponsiveImages Plugin for October CMS](https://github.com/webeks/oc-responsive-images-plugin)
* Logo was combined from resources from [Freepik](http://www.freepik.com)


 