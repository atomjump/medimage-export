# MedImage export capability. Alpha. 

This is an alternative interface to the MedImage app, which allows you to take a photo from inside AtomJump Messenger, or select a photo, and have it sent through to a medical desktop system via the MedImage Server. It uses a textual chatbot-style interface.

http://medimage.co.nz


Important install note: you must "sudo chmod 777 temp" the temporary folder plugins/medimage_export/temp/, at present.

We only recommend you install this software for evaluation / interest levels, and __not in a production environment__, at this stage. More careful checks are required around security of the 'temp' folder, and whether a file can be left in there under unusual circumstances.


## Installation with the AtomJump Appliance

See the AtomJump Messaging Appliance at: http://atomjump.org/wp/atomjump-messaging-appliance/

From the command-line, after having logged in, enter:

```
sudo git -C /jet/www/default/vendor/atomjump/loop-server pull
```

Then, click the following link to run a one-time update of your database:
* http://127.0.0.1:5100/vendor/atomjump/loop-server/update-indexes.php
* [or using your own IP address at http://myipaddress:5100/vendor/atomjump/loop-server/update-indexes.php]


```
sudo git -C /jet/www/default/vendor/atomjump/loop-server/plugins/ clone https://github.com/atomjump/medimage_export.git
sudo nano /jet/www/default/vendor/atomjump/loop-server/plugins/medimage_export/config/config.json
```

and edit, and then save these using Ctrl-O, and push enter.

```
{
"serverPath": "/jet/www/default/vendor/atomjump/loop-server/",
"staging": true,
"phpPath": "/jet/bin/php"
}
```

```
sudo chmod 777 /jet/www/default/vendor/atomjump/loop-server/plugins/medimage_export/temp

sudo nano /jet/www/default/vendor/atomjump/loop-server/config/config.json
```

and enter the "medimage_export" entry in the 1st "plugins" group (of the two plugin groups in the file).

```
"plugins" : [
"emoticons_basic",
....
"emoticons_large",
"medimage_export"
]
```




## Future work

* The plugin does not currently allow full-size photos (they are around 1200 pixels). You can, however, extend the size of the hi-res photos in your AtomJump Messenger config.json file. In future, we would likely want to run this as a secondary background process.

* Feedback on a successful upload only goes so far as telling you it is on the proxy server, and doesn't tell you if it has reached your target destination. See the upload.php file for further things to do here.

* It probably needs better reminders about changing the patient id when you first come back into the app.

* Multi-language support and a language file.

* See http://medimage.co.nz/building-an-alternative-client-to-medimage/ for notes on MedImage client installation when extending this app.

* More careful checks are required around security of the temp folder, and whether a file can be left in there.

* Because photos can be vertically oriented from this interface, some MedImage add-on tools, such as Wound Mapp, may not handle the orientation correctly, and appear to stretch photos.

* A group .pdf export, and individual existing photos within the group are yet to be completed.

* This pairs on an individual browser basis, but we could support a system-wide pairing.
