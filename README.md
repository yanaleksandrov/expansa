## TODO

- поддержка кеширования на промежуток времени (класс Cache) с сохранением в БД
- поддержка виртуальных страниц
- поддержка аттрибута maxlength и minlength для textarea и input text
- перенести классы Dir и File в единый класс Disk.
с синтаксисом:
new Dir($dirpath)     new File($filepath)
Disk::dir($dirpath)   Disk::file($filepath)

File->write
File->rewrite (put)
File->relocate       Dir->???  
File->copy           Dir->???  
File->setPermission  Dir->???

File->upload
File->grab
File->download
File->read

                     Dir->create           
                     Dir->clear
File->delete         Dir->delete
File->setName        Dir->rename
                     Dir->getFolders
                     Dir->getFoldersTree
                     Dir->getFiles
                     Dir->getSize
                     Dir->getPath