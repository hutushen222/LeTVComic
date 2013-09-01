LeTVComic
=========

实现一套乐视动漫（http://comic.letv.com/ ）的抓取系统。要求如下：
- 抓取乐视的所有动漫。
- 对于每部动漫，需要抓取的信息有：名字、监督、声优、国家、年代、类型、剧情介绍、封面图、分集链接。
- 对于未完结的动漫，定时更新分集链接。


安装
----

- 安装Composer，并安装依赖的库
- 导入`letv_comic_slim.sql`文件，创建数据库
- 修改`config.php`中数据库用户名和密码
- 修改`logs`, `templates/cache`, `storage/cache`, `storage/covers`目录的权限 `chmod 777`

使用
----

### 抓取**影片类型**

执行下面的命令即可（也可以不执行，应为在抓取动漫信息时也会动态创建）。

`php /path/to/scripts/letv_comic_types.php`

### 抓取**动漫详情和剧集**

初次抓取，执行下述命令，抓取全部的动漫信息：

`php /path/to/scripts/letv_comic_comics.php`

每天更新抓取，在上述命令后添加一些参数即可，如每天抓取1~3页：

`php /path/to/scripts/letv_comic_comics.php -s 3 [-e 1]`

**参数说明**：
- `-s` 起始页面，默认为分页的最大值
- `-e` 结束页面，默认为1

PS. 如果初次抓取过程中遇到问题，可以使用`-s`参数，从出错的页面继续进行抓取。

### 抓取**动漫封面**

封面抓取独立了出来，使用下述命令进行抓取：

`php /path/to/scripts/letv_comic_covers.php`


未完成（TODOs）
-------------

### 动漫剧集更新过程的优化

目前剧集更新的过程相当于重新抓取一遍，效率偏低。

### 日志报警功能

日志目前只是一个空壳，接收错误信息和日志信息，并将所有的信息打印到控制台上。
后续可以扩展将日志记录到数据库或第三方日志服务器，添加错误报警功能等。

### 动漫展示页面

实现一个动漫的视频展示页面，以及手动抓取动漫更新的功能。（抓取过程都封装好了，实现起来也比较简单。）

### 抓取代理功能

目前已经实现抓取代理服务器列表，代理服务器可用性检测以及基于cURL和file_get_contents()使用代理抓取内容的封装，
不过由于时间关系，以及在本地抓取的过程中没有遇到封IP的问题，目前没有实现代理切换抓取的功能。

### 多线程抓取

可以考虑基于`curl_multi_exec()`实现多线程抓取，提升抓取效率。


遇到的问题
---------

1. 抓取[樱桃小丸子 第2季](http://so.letv.com/comic/81067.html)详细信息时，遇到`Segmentation fault (core dumped)`错误，
原因是由于该动漫的剧集太多(616)，该程序中使用的HTML解析器SimpleHtmlDom在解析完该动漫之后，在解析后续的动漫时会触发上述错误。
可能的原因是SimpleHtmlDom的实现中的循环引用导致的，调用`SimpleHtmlDom::clear()`后该问题在我的机器上没有重现。

```
// clean up memory due to php5 circular references memory leak...
function clear() {
  // ...
}
```

-EOF-

