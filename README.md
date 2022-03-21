# MedImage export capability.

This is an alternative interface to the MedImage app, which allows you to take a photo from inside AtomJump Messenger, or select a photo, and have it sent through to a medical desktop system via the MedImage Server. It uses a textual chatbot-style interface.

The add-on also provides a PDF export of the whole forum.

http://medimage.co.nz


* For a __simple installation__, we recommend using the AtomJump Appliance; see below. Otherwise, please follow the steps in the __"Manual Installation"__ below.


## Installation with the AtomJump Appliance

See the AtomJump Messaging Appliance at: http://atomjump.org/wp/atomjump-messaging-appliance/

From the command-line, after having logged in, type (on one line with no linebreaks):

``eval "$(curl -fsSL -H 'Cache-Control: no-cache' https://git.atomjump.com/medimage_export.git/install)"``



and push 'Return'.

Then, click the following link to run a one-time update of your database:
* http://127.0.0.1:5100/vendor/atomjump/loop-server/update-indexes.php
* [or using your own IP address at http://__myipaddress__:5100/vendor/atomjump/loop-server/update-indexes.php]

Now you can take a photo, by clicking on the 'Upload' icon in any AtomJump Messaging window and follow the instructions to send photos through to your MedImage Server.



## Manual Installation


1. Get the project into your AtomJump Messaging plugins folder

```
cd plugins
git clone https://git.atomjump.com/medimage_export.git
```

2. You should copy the config/configORIGINAL.json to plugins/medimage_export/config/config.json, and edit the entries with your own server paths.

3. You must open up the temporary folder plugins/medimage_export/temp/, at present.

```
sudo mkdir plugins/medimage_export/temp
sudo chmod 777 plugins/medimage_export/temp
``` 

4. Add "medimage_export" to your loop-server config/config.json file's plugins array. e.g.

```
"plugins" : [
	...
	"otherplugin",
	"medimage_export"
]
```

Now you can take a photo, by clicking on the 'Upload' icon in any AtomJump Messaging window and follow the instructions to send photos through to your MedImage Server.

We only recommend you install this software for evaluation / interest levels, and __not in a production environment__, at this stage. More careful checks are required around security of the 'temp' folder, and whether a file can be left in there under unusual circumstances.





## Future work

* The plugin does not currently allow full-size photos (they are around 1200 pixels). You can, however, extend the size of the hi-res photos in your AtomJump Messenger config.json file. In future, we would likely want to run this as a secondary background process.

* Feedback on a successful upload only goes so far as telling you it is on the proxy server, and doesn't tell you if it has reached your target destination. See the upload.php file for further things to do here.

* It may need a better reminder about changing the patient id when you first come back into the forum.

* Multi-language support and a language file.

* See http://medimage.co.nz/building-an-alternative-client-to-medimage/ for notes on MedImage client installation when extending this app.

* Because photos can be vertically oriented from this interface, some MedImage add-on tools, such as Wound Mapp, may not handle the orientation correctly, and appear to stretch photos.

* This pairs on an individual browser basis, but we could support a system-wide pairing.

* UTF-8 support for PDF files: see https://github.com/fpdf-easytable/fpdf-easytable#fonts-and-utf8-support and http://www.fpdf.org/en/tutorial/tuto7.htm
