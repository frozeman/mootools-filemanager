<?php
/*
 * Script: FileManager.php
 *   MooTools FileManager - Backend for the FileManager Script
 *
 * Authors:
 *  - Christoph Pojer (http://cpojer.net) (author)
 *  - James Ehly (http://www.devtrench.com)
 *  - Fabian Vogelsteller (http://frozeman.de)
 *  - Ger Hobbelt (http://hebbut.net)
 *  - James Sleeman (http://code.gogo.co.nz)
 *
 * License:
 *   MIT-style license.
 *
 * Copyright:
 *   Copyright (c) 2009-2011 [Christoph Pojer](http://cpojer.net)
 *   Backend: FileManager & FMgr4Alias Copyright (c) 2011 [Ger Hobbelt](http://hobbelt.com)
 *
 * Dependencies:
 *   - Tooling.php
 *   - Image.class.php
 *   - getId3 Library
 *
 * Options:
 *   - directory: (string) The URI base directory to be used for the FileManager ('URI path' i.e. an absolute path here would be rooted at DocumentRoot: '/' == DocumentRoot)
 *   - assetBasePath: (string, optional) The URI path to all images and swf files used by the filemanager
 *   - thumbnailPath: (string) The URI path where the thumbnails of the pictures will be saved
 *   - mimeTypesPath: (string, optional) The filesystem path to the MimeTypes.ini file. May exist in a place outside the DocumentRoot tree.
 *   - dateFormat: (string, defaults to *j M Y - H:i*) The format in which dates should be displayed
 *   - maxUploadSize: (integer, defaults to *20280000* bytes) The maximum file size for upload in bytes
 *   - maxImageDimension: (array, defaults to *array('width' => 1024, 'height' => 768)*) The maximum number of pixels in height and width an image can have, if the user enables "resize on upload".
 *   - upload: (boolean, defaults to *false*) allow uploads, this is also set in the FileManager.js (this here is only for security protection when uploads should be deactivated)
 *   - destroy: (boolean, defaults to *false*) allow files to get deleted, this is also set in the FileManager.js (this here is only for security protection when file/directory delete operations should be deactivated)
 *   - create: (boolean, defaults to *false*) allow creating new subdirectories, this is also set in the FileManager.js (this here is only for security protection when dir creates should be deactivated)
 *   - move: (boolean, defaults to *false*) allow file and directory move/rename and copy, this is also set in the FileManager.js (this here is only for security protection when rename/move/copy should be deactivated)
 *   - download: (boolean, defaults to *false*) allow downloads, this is also set in the FileManager.js (this here is only for security protection when downloads should be deactivated)
 *   - allowExtChange: (boolean, defaults to *false*) allow the file extension to be changed when performing a rename operation.
 *   - safe: (boolean, defaults to *true*) If true, disallows 'exe', 'dll', 'php', 'php3', 'php4', 'php5', 'phps' and saves them as 'txt' instead.
 *   - chmod: (integer, default is 0777) the permissions set to the uploaded files and created thumbnails (must have a leading "0", e.g. 0777)
 *   - filter: (string, defaults to *null*) If not empty, this is a list of allowed mimetypes (overruled by the GET request 'filter' parameter: single requests can thus overrule the common setup in the constructor for this option)
 *   - thumbnailsMustGoThroughBackend: (boolean, defaults to *true*) When set to TRUE (default) all thumbnail requests go through the backend (onThumbnail). When set to FALSE, thumbnails will "shortcircuit" if they exist in the cache, saving roundtrips when using POST type propagateData
 *   - showHiddenFoldersAndFiles: (boolean, defaults to *false*) whether or not to show 'dotted' directories and files -- such files are considered 'hidden' on UNIX file systems
 *   - ViewIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given directory may be viewed.
 *     The parameter $action = 'view'.
 *   - DetailIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given file may be inspected (and the details listed).
 *     The parameter $action = 'detail'.
 *   - ThumbnailIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether a thumbnail of the given file may be shown.
 *     The parameter $action = 'thumbnail'.
 *   - UploadIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given file may be uploaded.
 *     The parameter $action = 'upload'.
 *   - DownloadIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given file may be downloaded.
 *     The parameter $action = 'download'.
 *   - CreateIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given subdirectory may be created.
 *     The parameter $action = 'create'.
 *   - DestroyIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given file / subdirectory tree may be deleted.
 *     The parameter $action = 'destroy'.
 *   - MoveIsAuthorized_cb (function/reference, default is *null*) authentication + authorization callback which can be used to determine whether the given file / subdirectory may be renamed, moved or copied.
 *     Note that currently support for copying subdirectories is missing.
 *     The parameter $action = 'move'.
 *   - URIpropagateData (array, default is *null*) the data elements which will be passed along as part of the generated request URIs, i.e. the thumbnail request URIs. Use this to pass custom data elements to the
 *     handler which delivers the thumbnails to the front-end.
 *
 * Obsoleted options:
 *   - maxImageSize: (integer, default is 1024) The maximum number of pixels in both height and width an image can have, if the user enables "resize on upload". (This option is obsoleted by the 'suggestedMaxImageDimension' option.)
 *
 *
 * About the action permissions (upload|destroy|create|move|download):
 *
 *     All the option "permissions" are set to FALSE by default. Developers should always SPECIFICALLY enable a permission to have that permission, for two reasons:
 *
 *     1. Developers forget to disable permissions, they don't forget to enable them (because things don't work!)
 *
 *     2. Having open permissions by default leaves potential for security vulnerabilities where those open permissions are exploited.
 *
 *
 * For all authorization hooks (callback functions) the following applies:
 *
 *     The callback should return TRUE for yes (permission granted), FALSE for no (permission denied).
 *     Parameters sent to the callback are:
 *       ($this, $action, $fileinfo)
 *     where $fileinfo is an array containing info about the file being uploaded, $action is a (string) identifying the current operation, $this is a reference to this FileManager instance.
 *     $action was included as a redundant parameter to each callback as a simple means to allow users to hook a single callback function to all the authorization hooks, without the need to create a wrapper function for each.
 *
 *     For more info about the hook parameter $fileinfo contents and a basic implementation, see further below (section 'Hooks: Detailed Interface Specification') and the examples in
 *     Demos/FM-common.php, Demos/manager.php and Demos/selectImage.php
 *
 *
 * Notes on relative paths and safety / security:
 *
 *   If any option is specifying a relative path, e.g. '../Assets' or 'Media/Stuff/', this is assumed to be relative to the request URI path,
 *   i.e. dirname($_SERVER['SCRIPT_NAME']).
 *
 *   Requests may post/submit relative paths as arguments to their FileManager events/actions in $_GET/$_POST, and those relative paths will be
 *   regarded as relative to the request URI handling script path, i.e. dirname($_SERVER['SCRIPT_NAME']) to make the most
 *   sense from bother server and client coding perspective.
 *
 *
 *   We also assume that any of the paths may be specified from the outside, so each path is processed and filtered to prevent malicious intent
 *   from succeeding. (An example of such would be an attacker posting his own 'destroy' event request requesting the destruction of
 *   '../../../../../../../../../etc/passwd' for example. In more complex rigs, the attack may be assisted through attacks at these options' paths,
 *   so these are subjected to the same scrutiny in here.)
 *
 *   All paths, absolute or relative, as passed to the event handlers (see the onXXX methods of this class) are ENFORCED TO ABIDE THE RULE
 *   'every path resides within the options['directory'] a.k.a. BASEDIR rooted tree' without exception.
 *   Because we can do without exceptions to important rules. ;-)
 *
 *   When paths apparently don't, they are coerced into adherence to this rule; when this fails, an exception is thrown internally and an error
 *   will be reported and the action temrinated.
 *
 *  'LEGAL URL paths':
 *
 *   Paths which adhere to the aforementioned rule are so-called LEGAL URL paths; their 'root' equals BASEDIR.
 *
 *   BASEDIR equals the path pointed at by the options['directory'] setting. It is therefore imperative that you ensure this value is
 *   correctly set up; worst case, this setting will equal DocumentRoot.
 *   In other words: you'll never be able to reach any file or directory outside this site's DocumentRoot directory tree, ever.
 *
 *
 *  Path transformations:
 *
 *   To allow arbitrary directory/path mapping algorithms to be applied (e.g. when implementing Alias support such as available in the
 *   derived class FileManagerWithAliasSupport), all paths are, on every change/edit, transformed from their LEGAL URL representation to
 *   their 'absolute URI path' (which is suitable to be used in links and references in HTML output) and 'absolute physical filesystem path'
 *   equivalents.
 *   By enforcing such a unidirectional transformation we implicitly support non-reversible and hard-to-reverse path aliasing mechanisms,
 *   e.g. complex regex+context based path manipulations in the server.
 *
 *
 *   When you need your paths to be restricted to the bounds of the options['directory'] tree (which is a subtree of the DocumentRoot based
 *   tree), you may wish to use the 'legal' class of path transformation member functions:
 *
 *   - legal2abs_url_path()
 *   - rel2abs_legal_url_path()
 *   - legal_url_path2file_path()
 *
 *   When you have a 'absolute URI path' or a path relative in URI space (implicitly relative to dirname($_SERVER['SCRIPT_NAME']) ), you can
 *   transform such a path to either a guaranteed-absolute URI space path or a filesystem path:
 *
 *   - rel2abs_url_path()
 *   - url_path2file_path()
 *
 *   Any other path transformations are ILLEGAL and DANGEROUS. The only other possibly legal transformation is from absolute URI path to
 *   BASEDIR-based LEGAL URL path, as the URI path space is assumed to be linear and contiguous. However, this operation is HIGHLY discouraged
 *   as it is a very strong indicator of other faulty logic, so we do NOT offer a method for this.
 *
 *
 * Hooks: Detailed Interface Specification:
 *
 *   All 'authorization' callback hooks share a common interface specification (function parameter set). This is by design, so one callback
 *   function can be used to process any and all of these events:
 *
 *   Function prototype:
 *
 *       function CallbackFunction($mgr, $action, &$info)
 *
 *   where
 *
 *       $msg:      (object) reference to the current FileManager class instance. Can be used to invoke public FileManager methods inside
 *                  the callback.
 *
 *       $action:   (string) identifies the event being processed. Can be one of these:
 *
 *                  'create'          create new directory
 *                  'move'            move or copy a file or directory
 *                  'destroy'         delete a file or directory
 *                  'upload'          upload a single file (when performing a bulk upload, each file will be uploaded individually)
 *                  'download'        download a file
 *                  'view'            show a directory listing (in either 'list' or 'thumb' mode)
 *                  'detail'          show detailed information about the file and, whn possible, provide a link to a (largish) thumbnail
 *                  'thumbnail'       send the thumbnail to the client (done this way to allow JiT thumbnail creation)
 *
 *       $info      (array) carries all the details. Some of which can even be manipulated if your callbac is more than just an
 *                  authentication / authorization checker. ;-)
 *                  For more detail, see the next major section.
 *
 *   The callback should return a boolean, where TRUE means the session/client is authorized to execute the action, while FALSE
 *   will cause the backend to report an authentication error and abort the action.
 *
 *  Exceptions throwing from the callback:
 *
 *   Note that you may choose to throw exceptions from inside the callback; those will be caught and transformed to proper error reports.
 *
 *   You may either throw any exceptions based on either the FileManagerException or Exception classes. When you format the exception
 *   message as "XYZ:data", where 'XYZ' is a alphanumeric-only word, this will be transformed to a i18n-support string, where
 *   'backend.XYZ' must map to a translation string (e.g. 'backend.nofile', see also the Language/Language.XX.js files) and the optional
 *   'data' tail will be appended to the translated message.
 *
 *
 * $info: the details:
 *
 *   Here is the list of $info members per $action event code:
 *
 *   'upload':
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the directory where the file is being uploaded. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'dir' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given directory.
 *
 *               'dir'                   (string) physical filesystem path to the directory where the file is being uploaded.
 *
 *               'raw_filename'          (string) the raw, unprocessed filename of the file being being uploaded, as specified by the client.
 *
 *                                       WARNING: 'raw_filename' may contain anything illegal, such as directory paths instead of just a filename,
 *                                                filesystem-illegal characters and what-not. Use 'name'+'extension' instead if you want to know
 *                                                where the upload will end up.
 *
 *               'name'                  (string) the filename, sans extension, of the file being uploaded; this filename is ensured
 *                                       to be both filesystem-legal, unique and not yet existing in the given directory.
 *
 *               'extension'             (string) the filename extension of the file being uploaded; this extension is ensured
 *                                       to be filesystem-legal.
 *
 *                                       Note that the file name extension has already been cleaned, including 'safe' mode processing,
 *                                       i.e. any uploaded binary executable will have been assigned the extension '.txt' already, when
 *                                       FileManager's options['safe'] is enabled.
 *
 *               'tmp_filepath'          (string) filesystem path pointing at the temporary storage location of the uploaded file: you can
 *                                       access the file data available here to optionally validate the uploaded content.
 *
 *               'mime'                  (string) the mime type as sniffed from the file
 *
 *               'mime_filter'           (optional, string) mime filter as specified by the client: a comma-separated string containing
 *                                       full or partial mime types, where a 'partial' mime types is the part of a mime type before
 *                                       and including the slash, e.g. 'image/'
 *
 *               'mime_filters'          (optional, array of strings) the set of allowed mime types, derived from the 'mime_filter' setting.
 *
 *               'size'                  (integer) number of bytes of the uploaded file
 *
 *               'maxsize'               (integer) the configured maximum number of bytes for any single upload
 *
 *               'overwrite'             (boolean) FALSE: the uploaded file will not overwrite any existing file, it will fail instead.
 *
 *                                       Set to TRUE (and adjust the 'name' and 'extension' entries as you desire) when you wish to overwrite
 *                                       an existing file.
 *
 *               'chmod'                 (integer) UNIX access rights (default: 0666) for the file-to-be-created (RW for user,group,world).
 *
 *                                       Note that the eXecutable bits have already been stripped before the callback was invoked.
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *
 *         Note that this request originates from a Macromedia Flash client: hence you'll need to use the
 *         $_POST[session_name()] value to manually set the PHP session_id() before you start your your session
 *         again.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *         The frontend-specified options.uploadAuthData items will be available as $_POST[] items.
 *
 *
 *  'download':
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the file to be downloaded. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'file' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given file.
 *
 *               'file'                  (string) physical filesystem path to the file being downloaded.
 *
 *               'mime'                  (string) the mime type as sniffed from the file
 *
 *               'mime_filter'           (optional, string) mime filter as specified by the client: a comma-separated string containing
 *                                       full or partial mime types, where a 'partial' mime types is the part of a mime type before
 *                                       and including the slash, e.g. 'image/'
 *
 *               'mime_filters'          (optional, array of strings) the set of allowed mime types, derived from the 'mime_filter' setting.
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *  'create': // create directory
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the parent directory of the directory being created. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'dir' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for this parent directory.
 *
 *               'dir'                   (string) physical filesystem path to the parent directory of the directory being created.
 *
 *               'raw_name'              (string) the name of the directory to be created, as specified by the client (unfiltered!)
 *
 *               'uniq_name'             (string) the name of the directory to be created, filtered and ensured to be both unique and
 *                                       not-yet-existing in the filesystem.
 *
 *               'newdir'                (string) the filesystem absolute path to the directory to be created; identical to:
 *                                           $newdir = $mgr->legal_url_path2file_path($legal_url . $uniq_name);
 *                                       Note the above: all paths are transformed from URI space to physical disk every time a change occurs;
 *                                       this allows us to map even not-existing 'directories' to possibly disparate filesystem locations.
 *
 *               'chmod'                 (integer) UNIX access rights (default: 0777) for the directory-to-be-created (RWX for user,group,world)
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *  'destroy':
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the file/directory to be deleted. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'file' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given file/directory.
 *
 *               'file'                  (string) physical filesystem path to the file/directory being deleted.
 *
 *               'mime'                  (string) the mime type as sniffed from the file / directory (directories are mime type: 'text/directory')
 *
 *               'mime_filter'           (optional, string) mime filter as specified by the client: a comma-separated string containing
 *                                       full or partial mime types, where a 'partial' mime types is the part of a mime type before
 *                                       and including the slash, e.g. 'image/'
 *
 *               'mime_filters'          (optional, array of strings) the set of allowed mime types, derived from the 'mime_filter' setting.
 *
 *                                       Note that the 'mime_filters', if any, are applied to the 'delete' operation in a special way: only
 *                                       files matching one of the mime types in this list will be deleted; anything else will remain intact.
 *                                       This can be used to selectively clean a directory tree.
 *
 *                                       The design idea behind this approach is that you are only allowed what you can see ('view'), so
 *                                       all 'view' restrictions should equally to the 'delete' operation.
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *  'move':  // move or copy!
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the source parent directory of the file/directory being moved/copied. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'dir' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given directory.
 *
 *               'dir'                   (string) physical filesystem path to the source parent directory of the file/directory being moved/copied.
 *
 *               'path'                  (string) physical filesystem path to the file/directory being moved/copied itself; this is the full source path.
 *
 *               'name'                  (string) the name itself of the file/directory being moved/copied; this is the source name.
 *
 *               'legal_newurl'          (string) LEGAL URI path to the target parent directory of the file/directory being moved/copied. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'dir' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given directory.
 *
 *               'newdir'                (string) physical filesystem path to the target parent directory of the file/directory being moved/copied;
 *                                       this is the full path of the directory where the file/directory will be moved/copied to. (filesystem absolute)
 *
 *               'newpath'               (string) physical filesystem path to the target file/directory being moved/copied itself; this is the full destination path,
 *                                       i.e. the full path of where the file/directory should be renamed/moved to. (filesystem absolute)
 *
 *               'newname'               (string) the target name itself of the file/directory being moved/copied; this is the destination name.
 *
 *                                       This filename is ensured to be both filesystem-legal, unique and not yet existing in the given target directory.
 *
 *               'rename'                (boolean) TRUE when a file/directory RENAME operation is requested (name change, staying within the same
 *                                       parent directory). FALSE otherwise.
 *
 *               'is_dir'                (boolean) TRUE when the subject is a directory itself, FALSE when it is a regular file.
 *
 *               'function'              (string) PHP call which will perform the operation. ('rename' or 'copy')
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *  'view':
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the directory being viewed/scanned. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'dir' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the scanned directory.
 *
 *               'dir'                   (string) physical filesystem path to the directory being viewed/scanned.
 *
 *               'collection'            (dual array of strings) arrays of files and directories (including '..' entry at the top when this is a
 *                                       subdirectory of the FM-managed tree): only names, not full paths. The files array is located at the
 *                                       ['files'] index, while the directories are available at the ['dirs'] index.
 *
 *               'mime_filter'           (optional, string) mime filter as specified by the client: a comma-separated string containing
 *                                       full or partial mime types, where a 'partial' mime types is the part of a mime type before
 *                                       and including the slash, e.g. 'image/'
 *
 *               'mime_filters'          (optional, array of strings) the set of allowed mime types, derived from the 'mime_filter' setting.
 *
 *               'guess_mime'            (boolean) TRUE when the mime type for each file in this directory will be determined using filename
 *                                       extension sniffing only; FALSE means the mime type will be determined using content sniffing, which
 *                                       is slower.
 *
 *               'list_type'             (string) the type of view requested: 'list' or 'thumb'.
 *
 *               'file_preselect'        (optional, string) filename of a file in this directory which should be located and selected.
 *                                       When found, the backend will provide an index number pointing at the corresponding JSON files[]
 *                                       entry to assist the front-end in jumping to that particular item in the view.
 *
 *               'preliminary_json'      (array) the JSON data collected so far; when ['status']==1, then we're performing a regular view
 *                                       operation (possibly as the second half of a copy/move/delete operation), when the ['status']==0,
 *                                       we are performing a view operation as the second part of another otherwise failed action, e.g. a
 *                                       failed 'create directory'.
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *  'detail':
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the file/directory being inspected. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'file' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given file.
 *
 *               'file'                  (string) physical filesystem path to the file being inspected.
 *
 *               'filename'              (string) the filename of the file being inspected. (Identical to 'basename($legal_url)')
 *
 *               'mime'                  (string) the mime type as sniffed from the file
 *
 *               'mime_filter'           (optional, string) mime filter as specified by the client: a comma-separated string containing
 *                                       full or partial mime types, where a 'partial' mime types is the part of a mime type before
 *                                       and including the slash, e.g. 'image/'
 *
 *               'mime_filters'          (optional, array of strings) the set of allowed mime types, derived from the 'mime_filter' setting.
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *  'thumbnail':
 *
 *           $info[] contains:
 *
 *               'legal_url'             (string) LEGAL URI path to the file/directory being thumbnailed. You may invoke
 *                                           $dir = $mgr->legal_url_path2file_path($legal_url);
 *                                       to obtain the physical filesystem path (also available in the 'file' $info entry, by the way!), or
 *                                           $url = $mgr->legal2abs_url_path($legal_url);
 *                                       to obtain the absolute URI path for the given file.
 *
 *               'file'                  (string) physical filesystem path to the file being inspected.
 *
 *               'filename'              (string) the filename of the file being inspected. (Identical to 'basename($legal_url)')
 *
 *               'mime'                  (string) the mime type as sniffed from the file
 *
 *               'mime_filter'           (optional, string) mime filter as specified by the client: a comma-separated string containing
 *                                       full or partial mime types, where a 'partial' mime types is the part of a mime type before
 *                                       and including the slash, e.g. 'image/'
 *
 *               'mime_filters'          (optional, array of strings) the set of allowed mime types, derived from the 'mime_filter' setting.
 *
 *               'requested_size'        (integer) the size (maximum width and height) in pixels of the thumbnail to be produced.
 *
 *               'mode'                  (string) 'image' (default): produce the thumbnail binary image data itself. 'json': return a JSON 
 *                                       response listing the URL to the actual thumbnail image.
 *
 *               'validation_failure'    (string) NULL: no validation error has been detected before the callback was invoked; non-NULL, e.g.
 *                                       "nofile": the string passed as message parameter of the FileManagerException, which will be thrown
 *                                       after the callback has returned. (You may alter the 'validation_failure' string value to change the
 *                                       reported error, or set it to NULL to turn off the validation error report entirely -- we assume you
 *                                       will have corrected the other fileinfo[] items as well, when resetting the validation error.
 *
 *         The frontend-specified options.propagateData items will be available as $_GET[] or $_POST[] items, depending on the frontend
 *         options.propagateType setting.
 *
 *
 *
 * Developer Notes:
 *
 * - member functions which have a commented out 'static' keyword have it removed by design: it makes for easier overloading through
 *   inheritance that way and meanwhile there's no pressing need to have those (public) member functions acccessible from the outside world
 *   without having an instance of the FileManager class itself round at the same time.
 */

// ----------- compatibility checks ----------------------------------------------------------------------------
if (version_compare(PHP_VERSION, '5.2.0') < 0)
{
	// die horribly: server does not match our requirements!
	header('HTTP/1.0 500 FileManager requires PHP 5.2.0 or later', true, 500); // Internal server error
	throw Exception('FileManager requires PHP 5.2.0 or later');   // this exception will most probably not be caught; that's our intent!
}

if (function_exists('UploadIsAuthenticated'))
{
	// die horribly: user has not upgraded his callback hook(s)!
	header('HTTP/1.0 500 FileManager callback has not been upgraded!', true, 500); // Internal server error
	throw Exception('FileManager callback has not been upgraded!');   // this exception will most probably not be caught; that's our intent!
}

//-------------------------------------------------------------------------------------------------------------

if (!defined('DEVELOPMENT')) define('DEVELOPMENT', 0);   // make sure this #define is always known to us



require_once(str_replace('\\', '/', dirname(__FILE__)) . '/Tooling.php');
require_once(str_replace('\\', '/', dirname(__FILE__)) . '/Image.class.php');
require_once(str_replace('\\', '/', dirname(__FILE__)) . '/Assets/getid3/getid3.php');



// the jpeg quality for the largest thumbnails (smaller ones are automatically done at increasingly higher quality)
define('MTFM_THUMBNAIL_JPEG_QUALITY', 75);

// the number of directory levels in the thumbnail cache; set to 2 when you expect to handle huge image collections.
//
// Note that each directory level distributes the files evenly across 256 directories; hence, you may set this
// level count to 2 when you expect to handle more than 32K images in total -- as each image will have two thumbnails:
// a 48px small one and a 250px large one.
define('MTFM_NUMBER_OF_DIRLEVELS_FOR_CACHE', 1);

// minimum number of cached getID3 results; cache is automatically pruned
define('MTFM_MIN_GETID3_CACHESIZE', 16);



class FileManager
{
	protected $options;
	protected $getid3;
	protected $getid3_cache;
	protected $getid3_cache_lru_ts;
	protected $icon_cache;

	public function __construct($options)
	{
		$this->options = array_merge(array(
			/*
			 * Note that all default paths as listed below are transformed to DocumentRoot-based paths
			 * through the getRealPath() invocations further below:
			 */
			'directory' => null,                                       // MUST be in the DocumentRoot tree
			'assetBasePath' => null,                                   // may sit outside options['directory'] but MUST be in the DocumentRoot tree
			'thumbnailPath' => null,                                   // may sit outside options['directory'] but MUST be in the DocumentRoot tree
			'mimeTypesPath' => str_replace('\\', '/', dirname(__FILE__)) . '/MimeTypes.ini',   // an absolute filesystem path anywhere; when relative, it will be assumed to be against SERVER['SCRIPT_NAME']
			'dateFormat' => 'j M Y - H:i',
			'maxUploadSize' => 2600 * 2600 * 3,
			// 'maxImageSize' => 99999,                                 // obsoleted, replaced by 'suggestedMaxImageDimension'
			// Xinha: Allow to specify the "Resize Large Images" tolerance level.
			'maxImageDimension' => array('width' => 1024, 'height' => 768),
			'upload' => false,
			'destroy' => false,
			'create' => false,
			'move' => false,
			'download' => false,
			/* ^^^ this last one is easily circumnavigated if it's about images: when you can view 'em, you can 'download' them anyway.
			 *     However, for other mime types which are not previewable / viewable 'in their full bluntal nugity' ;-) , this will
			 *     be a strong deterent.
			 *
			 *     Think Springer Verlag and PDFs, for instance. You can have 'em, but only /after/ you've ...
			 */
			'allowExtChange' => false,
			'safe' => true,
			'filter' => null,
			'chmod' => 0777,
			'ViewIsAuthorized_cb' => null,
			'DetailIsAuthorized_cb' => null,
			'ThumbnailIsAuthorized_cb' => null,
			'UploadIsAuthorized_cb' => null,
			'DownloadIsAuthorized_cb' => null,
			'CreateIsAuthorized_cb' => null,
			'DestroyIsAuthorized_cb' => null,
			'MoveIsAuthorized_cb' => null,
			'thumbnailsMustGoThroughBackend' => false, // If set true (default) all thumbnail requests go through the backend (onThumbnail), if false, thumbnails will "shortcircuit" if they exist, saving roundtrips when using POST type propagateData
			'showHiddenFoldersAndFiles' => false,      // Hide dot dirs/files ?
			'URIpropagateData' => null
		), (is_array($options) ? $options : array()));

		// transform the obsoleted/deprecated options:
		if (!empty($this->options['maxImageSize']) && $this->options['maxImageSize'] != 1024 && $this->options['maxImageDimension']['width'] == 1024 && $this->options['maxImageDimension']['height'] == 768)
		{
			$this->options['maxImageDimension'] = array('width' => $this->options['maxImageSize'], 'height' => $this->options['maxImageSize']);
		}

		$assumed_root = @realpath($_SERVER['DOCUMENT_ROOT']);
		$assumed_root = str_replace('\\', '/', $assumed_root);
		$assumed_root = rtrim($assumed_root, '/');
		$this->options['assumed_root_filepath'] = $assumed_root;

		// only calculate the guestimated defaults when they are indeed required:
		if ($this->options['directory'] == null || $this->options['assetBasePath'] == null || $this->options['thumbnailPath'] == null)
		{
			$my_path = @realpath(dirname(__FILE__));
			$my_path = str_replace('\\', '/', $my_path);
			if (!FileManagerUtility::endsWith($my_path, '/'))
			{
				$my_path .= '/';
			}
			$my_assumed_url_path = str_replace($assumed_root, '', $my_path);

			// we throw an Exception here because when these do not apply, the user should have specified all three these entries!
			if (empty($assumed_root) || empty($my_path) || !FileManagerUtility::startsWith($my_path, $assumed_root))
			{
				FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__);
				throw new FileManagerException('nofile');
			}

			if ($this->options['directory'] == null)
			{
				$this->options['directory'] = $my_assumed_url_path . '../../Demos/Files/';
			}
			if ($this->options['assetBasePath'] == null)
			{
				$this->options['assetBasePath'] = $my_assumed_url_path . '../../Demos/Files/../../Assets/';
			}
			if ($this->options['thumbnailPath'] == null)
			{
				$this->options['thumbnailPath'] = $my_assumed_url_path . '../../Demos/Files/../../Assets/Thumbs/';
			}
		}

		/*
		 * make sure we start with a very predictable and LEGAL options['directory'] setting, so that the checks applied to the
		 * (possibly) user specified value for this bugger acvtually can check out okay AS LONG AS IT'S INSIDE the DocumentRoot-based
		 * directory tree:
		 */
		$new_root = $this->options['directory'];
		$this->options['directory'] = '/';      // use DocumentRoot temporarily as THE root for this optional transform
		$this->options['directory'] = self::enforceTrailingSlash($this->rel2abs_url_path($new_root));

		$this->options['assumed_base_filepath'] = $this->url_path2file_path($this->options['directory']);

		// now that the correct options['directory'] has been set up, go and check/clean the other paths in the options[]:

		$this->options['thumbnailPath'] = self::enforceTrailingSlash($this->rel2abs_url_path($this->options['thumbnailPath']));
		$this->options['assetBasePath'] = self::enforceTrailingSlash($this->rel2abs_url_path($this->options['assetBasePath']));

		$this->options['mimeTypesPath'] = @realpath($this->options['mimeTypesPath']);
		if (empty($this->options['mimeTypesPath']))
		{
			FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__);
			throw new FileManagerException('nofile');
		}
		$this->options['mimeTypesPath'] = str_replace('\\', '/', $this->options['mimeTypesPath']);

		// getID3 is slower as it *copies* the image to the temp dir before processing: see GetDataImageSize().
		// This is done as getID3 can also analyze *embedded* images, for which this approach is required.
		$this->getid3 = new getID3();
		$this->getid3->encoding = 'UTF-8';

		// getid3_cache stores the info arrays; gitid3_cache_lru_ts stores a 'timestamp' counter to track LRU: 'timestamps' older than threshold are discarded when cache is full
		$this->getid3_cache = array();
		$this->getid3_cache_lru_ts = 0;

		$this->icon_cache = array(array(), array());

		if (!headers_sent())
		{
			header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
			header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		}
	}

	/**
	 * @return array the FileManager options and settings.
	 */
	public function getSettings()
	{
		return array_merge(array(
				'basedir' => $this->url_path2file_path($this->options['directory'])
		), $this->options);
	}




	/**
	 * Central entry point for any client side request.
	 */
	public function fireEvent($event = null)
	{
		$event = !empty($event) ? 'on' . ucfirst($event) : null;
		if (!$event || !method_exists($this, $event)) $event = 'onView';

		$this->{$event}();
	}






	/**
	 * Generalized 'view' handler, which produces a directory listing.
	 *
	 * Return the directory listing in a nested array, suitable for JSON encoding.
	 */
	protected function _onView($legal_url, $json, $mime_filter, $list_type, $file_preselect_arg = null, $filemask = '*')
	{
		$v_ex_code = 'nofile';

		$dir = $this->legal_url_path2file_path($legal_url);
		$doubledot = null;
		$coll = null;
		if (is_dir($dir))
		{
			$coll = $this->scandir($dir, $filemask, false, 0, ($this->options['showHiddenFoldersAndFiles'] ? ~GLOB_NOHIDDEN : ~0));
			if ($coll !== false)
			{
				/*
				 * To ensure '..' ends up at the very top of the view, no matter what the other entries in $coll['dirs'][] are made of,
				 * we pop the last element off the array, check whether it's the double-dot, and if so, keep it out while we
				 * let the sort run.
				 */
				$doubledot = array_pop($coll['dirs']);
				if ($doubledot !== null && $doubledot !== '..')
				{
					$coll['dirs'][] = $doubledot;
					$doubledot = null;
				}
				natcasesort($coll['dirs']);
				natcasesort($coll['files']);

				$v_ex_code = null;
			}
		}
		FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__);

		$mime_filters = $this->getAllowedMimeTypes($mime_filter);

		// remove the imageinfo() call overhead per file for very large directories; just guess at the mimetye from the filename alone.
		// The real mimetype will show up in the 'details' view anyway! This is only for the 'filter' function:
		$just_guess_mime = true; // (count($coll['files']) + count($coll['dirs']) > 100);

		$fileinfo = array(
				'legal_url' => $legal_url,
				'dir' => $dir,
				'collection' => $coll,
				'mime_filter' => $mime_filter,
				'mime_filters' => $mime_filters,
				'guess_mime' => $just_guess_mime,
				'list_type' => $list_type,
				'file_preselect' => $file_preselect_arg,
				'preliminary_json' => $json,
				'validation_failure' => $v_ex_code
			);

		if (!empty($this->options['ViewIsAuthorized_cb']) && function_exists($this->options['ViewIsAuthorized_cb']) && !$this->options['ViewIsAuthorized_cb']($this, 'view', $fileinfo))
		{
			$v_ex_code = $fileinfo['validation_failure'];
			if (empty($v_ex_code)) $v_ex_code = 'authorized';
		}
		FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__, $fileinfo);
		if (!empty($v_ex_code))
			throw new FileManagerException($v_ex_code);

		$legal_url = $fileinfo['legal_url'];
		$dir = $fileinfo['dir'];
		$coll = $fileinfo['collection'];
		$mime_filter = $fileinfo['mime_filter'];
		$mime_filters = $fileinfo['mime_filters'];
		$just_guess_mime = $fileinfo['guess_mime'];
		$list_type = $fileinfo['list_type'];
		$file_preselect_arg = $fileinfo['file_preselect'];
		$json = $fileinfo['preliminary_json'];

		$file_preselect_index = -1;
		$out = array(array(), array());

		$_iconspec = 'is.dir';
		$_icon = FileManagerUtility::rawurlencode_path($this->getIcon($_iconspec, true));
		$_thumb48 = FileManagerUtility::rawurlencode_path($this->getIcon($_iconspec, false));
		$_thumb250 = $_thumb48;
		if ($list_type == 'thumb')
		{
			$_thumb = $_thumb48;
		}
		else
		{
			$_thumb = $_icon;
		}


		$mime = 'text/directory';
		$iconspec = false;
		$thumb = null;
		$thumb48 = null;
		//$thumb250 = null;
		$icon = null;

		if ($doubledot !== null)
		{
			$filename = '..';

			$url = $legal_url . $filename;

			// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
			$file = $this->legal_url_path2file_path($url);

			$iconspec = 'is.dir_up';

			$thumb48 = FileManagerUtility::rawurlencode_path($this->getIcon($iconspec, false));
			//$thumb250 = $thumb48;

			$icon = FileManagerUtility::rawurlencode_path($this->getIcon($iconspec, true));

			if ($list_type == 'thumb')
			{
				$thumb = $thumb48;
			}
			else
			{
				$thumb = $icon;
			}

			$url_p = FileManagerUtility::rawurlencode_path($url);

			$out[1][] = array(
					'path' => $url_p,
					'name' => $filename,
					//'date' => date($this->options['dateFormat'], @filemtime($file)),
					'mime' => $mime,
					'thumbnail' => $thumb,
					'thumbnail48' => $thumb48,
					//'thumbnail250' => $thumb250,
					//'size' => @filesize($file),
					'icon' => $icon
				);
		}

		// now precalc the directory-common items (a.k.a. invariant computation / common subexpression hoisting)
		$iconspec_d = 'is.dir';

		$thumb48_d = FileManagerUtility::rawurlencode_path($this->getIcon($iconspec_d, false));
		//$thumb250_d = $thumb48_d;

		$icon_d = FileManagerUtility::rawurlencode_path($this->getIcon($iconspec_d, true));

		if ($list_type == 'thumb')
		{
			$thumb_d = $thumb48_d;
		}
		else
		{
			$thumb_d = $icon_d;
		}

		foreach ($coll['dirs'] as $filename)
		{
			$url = $legal_url . $filename;

			$url_p = FileManagerUtility::rawurlencode_path($url);

			$out[1][] = array(
					'path' => $url_p,
					'name' => $filename,
					//'date' => date($this->options['dateFormat'], @filemtime($file)),
					'mime' => $mime,
					'thumbnail' => $thumb_d,
					'thumbnail48' => $thumb48_d,
					//'thumbnail250' => $thumb250,
					//'size' => @filesize($file),
					'icon' => $icon_d
				);
		}

		FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__);

		/*
		 * ... and another bit of invariant computation: this time it's a bit more complex, but the mkEventHandlerURL() call is rather costly,
		 * so we do that one as a 'template' and str_replace() -- which is fast -- the template variables in there:
		 */
		$thumb_tpl = $this->mkEventHandlerURL(array(
				'event' => 'thumbnail',
				// directory and filename of the ORIGINAL image should follow next:
				'directory' => $legal_url,
				'file' => '..F..',
				'size' => '..S..',          // thumbnail suitable for 'view/type=thumb' list views
				'filter' => $mime_filter
			));
		$thumb_tpl48 = str_replace('..S..', '48', $thumb_tpl);
		//$thumb_tpl250 = str_replace('..S..', '250', $thumb_tpl);

		$idx = 0;
		//$next_reqd_mapping_idx = array_pop($coll['special_indir_mappings'][1]);

		foreach ($coll['files'] as $filename)
		{
			$mime = 'bogus/bogus';
			$thumb = $_thumb;
			$thumb48 = $_thumb48;
			$thumb250 = $_thumb250;
			$icon = $_icon;
			$iconspec = $_iconspec;

			$url = $legal_url . $filename;

			// no need to transform URL to FILE path as the filename will remain intact (unless we've got some really contrived aliasing in FileManagerWithAliasSupport: we don't care too much here about such wicked mappings, as speed is paramount)
			if (!$just_guess_mime)
			{
				$file = $this->legal_url_path2file_path($url);

				$mime = $this->getMimeType($file, false);
				$iconspec = basename($file);
			}
			else
			{
				$mime = $this->getMimeType($filename, true);
				$iconspec = $filename;
			}
			if (!$this->IsAllowedMimeType($mime, $mime_filters))
				continue;

			if ($filename == $file_preselect_arg)
			{
				$file_preselect_index = $idx;
			}

			if (FileManagerUtility::startsWith($mime, 'image/'))
			{
				/*
				 * offload the thumbnailing process to another event ('event=thumbnail') to be fired by the client
				 * when it's time to render the thumbnail: the offloading helps us tremendously in coping with large
				 * directories:
				 * WE simply assume the thumbnail will be there, so we don't even need to check for its existence
				 * (which saves us one more file_exists() per item at the very least). And when it doesn't, that's
				 * for the event=thumbnail handler to worry about (creating the thumbnail on demand or serving
				 * a generic icon image instead).
				 */

				$thumb48 = false;
				//$thumb250 = false;

				if (0)
				{
					/*
					 * DISABLED PERMANENTLY. This *may* look like smart code, but it is not. The dirscan result is
					 * long-lived on the client side (particularly for large directories, where you can browse multiple
					 * pages' worth of directory view: all that data originates from a single request and is cached
					 * client-side.
					 * Hence any thumbnails being generated during the browsing of that directory do not get to
					 * 'short circuit' anyway, as the client-side cached dirscan output still lists the PHP-based
					 * requests.
					 *
					 * Besides, there's another issue with large lists: the server is bombarded with thumbnail
					 * requests, lamost like a DoS attack. So the client should really queue the thumbnail requests
					 * for the thumb view, irrespective of the propagateType being POST or GET.
					 *
					 * Last, and minor, quible with this: when the thumbnail cache is purged while a directory is
					 * browsed, the user must hit [F5] to refresh the entire filemanger too receive an up-to-date
					 * scandir, i.e. one with PHP-based thumbnail requests. (For onDetail, on the other hand, such a
					 * short-cut is fine as a mishap there can simply be recovered by clicking on the entry in the
					 * thumb/list directory view again: that's minimal fuss. The same recovery for a dirview is
					 * non-intuitive and not recognized by users: browse to other directory and then back again. Of
					 * course that non-intuitive 'fix' only works if you actually have multiple directories to view.
					 * User tests show the only thing that makes sense at all is hitting [F5] anyway and that is
					 * regarded as a nuisance.)
					 *
					 * I don't mind this adds 'one more round trip' to the propagateType=POST approach; the shortcut
					 * is simply causing too much bother for the users. And that extra trip is hidden among the other
					 * thumbnail requests anyway: a fast image fetch, while the other thumbnails are requested/generated.
					 *
					 * And besides: this 'shortcut' reintroduced the previously optimized-out file_exist per file:
					 * this time around, it's at least one extra file_exists() check in the thumbnail cache tree, and we
					 * could do very well without it, particularly for large directories where every bit of file access
					 * is slowing this bugger down, while the user is twiddling his thumbs.
					 */
					if (!$this->options['thumbnailsMustGoThroughBackend'])
					{
						$thumb48  = $this->getThumb($url, $file, 48, 48, true);
						//$thumb250 = $this->getThumb($url, $file, 250, 250, true);
					}
				}

				if ($thumb48 === false)
				{
					$thumb48 = str_replace('..F..', FileManagerUtility::rawurlencode_path($filename), $thumb_tpl48);
				}
				else
				{
					$thumb48 = FileManagerUtility::rawurlencode_path($thumb48);
				}

				//if ($thumb250 === false)
				//{
				//  $thumb250 = str_replace('..F..', FileManagerUtility::rawurlencode_path($filename), $thumb_tpl250);
				//}
				//else
				//{
				//  $thumb250 = FileManagerUtility::rawurlencode_path($thumb250);
				//}
			}
			else
			{
				$thumb48 = FileManagerUtility::rawurlencode_path($this->getIcon($iconspec, false));
				//$thumb250 = $thumb48;
			}
			$icon = FileManagerUtility::rawurlencode_path($this->getIcon($iconspec, true));

			if ($list_type == 'thumb')
			{
				$thumb = $thumb48;
			}
			else
			{
				$thumb = $icon;
			}

			$url_p = FileManagerUtility::rawurlencode_path($url);

			$out[0][] = array(
					'path' => $url_p,
					'name' => $filename,
					//'date' => date($this->options['dateFormat'], @filemtime($file)),
					'mime' => $mime,
					'thumbnail' => $thumb,
					'thumbnail48' => $thumb48,
					//'thumbnail250' => $thumb250,
					//'size' => @filesize($file),
					'icon' => $icon
				);
			$idx++;

			if (0)
			{
				// help PHP when 'doing' large image directories: reset the timeout for each thumbnail / entry we produce:
				//   http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time
				set_time_limit(max(30, ini_get('max_execution_time')));
			}
		}

		FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__, $idx);
		//die(json_encode(array('coll' => $out)));

		//$thumb48 = FileManagerUtility::rawurlencode_path($this->getIcon('is.dir', false));
		//$icon = FileManagerUtility::rawurlencode_path($this->getIcon('is.dir', true));
		//if ($list_type == 'thumb')
		//{
		//  $thumb = $thumb48;
		//}
		//else
		//{
		//  $thumb = $icon;
		//}
		return array_merge((is_array($json) ? $json : array()), array(
				'root' => substr($this->options['directory'], 1),
				'path' => $legal_url,                                  // is relative to options['directory']
				'dir' => array(
					'path' => FileManagerUtility::rawurlencode_path($legal_url),
					'name' => pathinfo($legal_url, PATHINFO_BASENAME),
					'date' => date($this->options['dateFormat'], @filemtime($dir)),
					'mime' => 'text/directory',
					'thumbnail' => $thumb_d,
					'thumbnail48' => $thumb48_d,
					//'thumbnail250' => $thumb48_d,
					'icon' => $icon_d
				),
				'preselect_index' => ($file_preselect_index >= 0 ? $file_preselect_index + count($out[1]) + 1 : 0),
				'preselect_name' => ($file_preselect_index >= 0 ? $file_preselect_arg : null),
				'dirs' => $out[1],
				'files' => $out[0]
			));
	}

	/**
	 * Process the 'view' event (default event fired by fireEvent() method)
	 *
	 * Returns a JSON encoded directory view list.
	 *
	 * Expected parameters:
	 *
	 * $_POST['directory']     path relative to basedir a.k.a. options['directory'] root
	 *
	 * $_POST['file_preselect']     optional filename or path:
	 *                         when a filename, this is the filename of a file in this directory
	 *                         which should be located and selected. When found, the backend will
	 *                         provide an index number pointing at the corresponding JSON files[]
	 *                         entry to assist the front-end in jumping to that particular item
	 *                         in the view.
	 *
	 *                         when a path, it is either an absolute or a relative path:
	 *                         either is assumed to be a URI URI path, i.e. rooted at
	 *                           DocumentRoot.
	 *                         The path will be transformed to a LEGAL URI path and
	 *                         will OVERRIDE the $_POST['directory'] path.
	 *                         Otherwise, this mode acts as when only a filename was specified here.
	 *                         This mode is useful to help a frontend to quickly jump to a file
	 *                         pointed at by a URI.
	 *
	 *                         N.B.: This also the only entry which accepts absolute URI paths and
	 *                               transforms them to LEGAL URI paths.
	 *
	 *                         When the specified path is illegal, i.e. does not reside inside the
	 *                         options['directory']-rooted LEGAL URI subtree, it will be discarded
	 *                         entirely (as all file paths, whether they are absolute or relative,
	 *                         must end up inside the options['directory']-rooted subtree to be
	 *                         considered manageable files) and the process will continue as if
	 *                         the $_POST['file_preselect'] entry had not been set.
	 *
	 * $_POST['filter']        optional mimetype filter string, amy be the part up to and
	 *                         including the slash '/' or the full mimetype. Only files
	 *                         matching this (set of) mimetypes will be listed.
	 *                         Examples: 'image/' or 'application/zip'
	 *
	 * $_POST['type']          'thumb' will produce a list view including thumbnail and other
	 *                         information with each listed file; other values will produce
	 *                         a basic list view (similar to Windows Explorer 'list' view).
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                  0 for error; nonzero for success
	 *
	 * error                   error message
	 *
	 * Next to these, the JSON encoded output will, with high probability, include a
	 * list view of the parent or 'basedir' as a fast and easy fallback mechanism for client side
	 * viewing code. However, severe and repetitive errors may not produce this
	 * 'fallback view list' so proper client code should check the 'status' field in the
	 * JSON output.
	 */
	protected function onView()
	{
		// try to produce the view; if it b0rks, retry with the parent, until we've arrived at the basedir:
		// then we fail more severely.

		$emsg = null;
		$jserr = array(
				'status' => 1
			);

		$mime_filter = $this->getPOSTparam('filter', $this->options['filter']);
		$list_type = ($this->getPOSTparam('type') != 'thumb' ? 'list' : 'thumb');

		try
		{
			$dir_arg = $this->getPOSTparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);

			$file_preselect_arg = $this->getPOSTparam('file_preselect');
			try
			{
				if (!empty($file_preselect_arg))
				{
					// check if this a path instead of just a basename, then convert to legal_url and split across filename and directory.
					if (strpos($file_preselect_arg, '/') !== false)
					{
						// this will also convert a relative path to an absolute path before transforming it to a LEGAL URI path:
						$legal_presel = $this->abs2legal_url_path($file_preselect_arg);

						$prseli = pathinfo($legal_presel);
						$file_preselect_arg = $prseli['basename'];
						// override the directory!
						$legal_url = $prseli['dirname'];
						$legal_url = self::enforceTrailingSlash($legal_url);
					}
					else
					{
						$file_preselect_arg = pathinfo($file_preselect_arg, PATHINFO_BASENAME);
					}
				}
			}
			catch(FileManagerException $e)
			{
				// discard the preselect input entirely:
				$file_preselect_arg = null;
			}
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();
			$legal_url = '/';
			$file_preselect_arg = null;
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything it may not be a translation keyword in the message...
			$emsg = $e->getMessage();
			$legal_url = '/';
			$file_preselect_arg = null;
		}

		// loop until we drop below the bottomdir; meanwhile getDir() above guarantees that $dir is a subdir of bottomdir, hence dir >= bottomdir.
		$original_legal_url = $legal_url;
		do
		{
			try
			{
				$rv = $this->_onView($legal_url, $jserr, $mime_filter, $list_type, $file_preselect_arg);

				if (!headers_sent()) header('Content-Type: application/json');

				echo json_encode($rv);
				return;
			}
			catch(FileManagerException $e)
			{
				if ($emsg === null)
					$emsg = $e->getMessage();
			}
			catch(Exception $e)
			{
				// catching other severe failures; since this can be anything it may not be a translation keyword in the message...
				if ($emsg === null)
					$emsg = $e->getMessage();
			}

			// step down to the parent dir and retry:
			$legal_url = self::getParentDir($legal_url);
			$file_preselect_arg = null;

			$jserr['status']++;

		} while ($legal_url !== false);

		$this->modify_json4exception($jserr, $emsg . ' : path :: ' . $original_legal_url);

		if (!headers_sent()) header('Content-Type: application/json');

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode($jserr);
	}

	/**
	 * Process the 'detail' event
	 *
	 * Returns a JSON encoded HTML chunk describing the specified file (metadata such
	 * as size, format and possibly a thumbnail image as well)
	 *
	 * Expected parameters:
	 *
	 * $_POST['directory']     path relative to basedir a.k.a. options['directory'] root
	 *
	 * $_POST['file']          filename (including extension, of course) of the file to
	 *                         be detailed.
	 *
	 * $_POST['filter']        optional mimetype filter string, amy be the part up to and
	 *                         including the slash '/' or the full mimetype. Only files
	 *                         matching this (set of) mimetypes will be listed.
	 *                         Examples: 'image/' or 'application/zip'
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                  0 for error; nonzero for success
	 *
	 * error                   error message
	 */
	protected function onDetail()
	{
		$emsg = null;
		$jserr = array(
				'status' => 1
			);

		try
		{
			$v_ex_code = 'nofile';

			$file_arg = $this->getPOSTparam('file');

			$dir_arg = $this->getPOSTparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);

			$mime_filter = $this->getPOSTparam('filter', $this->options['filter']);
			$mime_filters = $this->getAllowedMimeTypes($mime_filter);

			$filename = null;
			$file = null;
			$mime = null;
			if (!empty($file_arg))
			{
				$filename = pathinfo($file_arg, PATHINFO_BASENAME);
				$legal_url .= $filename;
				// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
				$file = $this->legal_url_path2file_path($legal_url);

				if (is_readable($file))
				{
					if (is_file($file))
					{
						$mime = $this->getMimeType($file);
						if (!$this->IsAllowedMimeType($mime, $mime_filters))
							$v_ex_code = 'extension';
						else
							$v_ex_code = null;
					}
					else if (is_dir($file))
					{
						$mime = 'text/directory';
						$v_ex_code = null;
					}
				}
			}

			$fileinfo = array(
					'legal_url' => $legal_url,
					'file' => $file,
					'filename' => $filename,
					'mime' => $mime,
					'mime_filter' => $mime_filter,
					'mime_filters' => $mime_filters,
					'validation_failure' => $v_ex_code
				);

			if (!empty($this->options['DetailIsAuthorized_cb']) && function_exists($this->options['DetailIsAuthorized_cb']) && !$this->options['DetailIsAuthorized_cb']($this, 'detail', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			//$file = $fileinfo['file'];
			$filename = $fileinfo['filename'];
			//$mime = $fileinfo['mime'];
			$mime_filter = $fileinfo['mime_filter'];
			$mime_filters = $fileinfo['mime_filters'];

			$jserr = $this->extractDetailInfo($jserr, $legal_url, $mime_filter, $mime_filters);

			if (!headers_sent()) header('Content-Type: application/json');

			echo json_encode($jserr);
			return;
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything and should only happen in the direst of circumstances, we don't bother translating
			$emsg = $e->getMessage();
		}

		$this->modify_json4exception($jserr, $emsg);

		if (!headers_sent()) header('Content-Type: application/json');

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode($jserr);
	}

	/**
	 * Process the 'thumbnail' event
	 *
	 * Returns either the binary content of the requested thumbnail or the binary content of a replacement image.
	 *
	 * Technical info: this function is assumed to be fired from a <img src="..."> URI or similar and must produce
	 * the content of an image.
	 * It is used in conjection with the 'view/list=thumb' view mode of the FM client: the 'view' list, as
	 * produced by us, contains specially crafted URLs pointing back at us (the 'event=thumbnail' URLs) to
	 * enable FM to cope much better with large image collections by having the entire thumbnail checking
	 * and creation process offloaded to this Just-in-Time subevent.
	 *
	 * By not loading the 'view' event with the thumbnail precreation/checking effort, it can respond
	 * much faster or at least not timeout in the backend for larger image sets in any directory.
	 * ('view' simply assumes the thumbnail will be there, hence reducing its own workload with at least
	 * 1 file_exists() plus worst-case one GD imageinfo + imageresample + extras per image in the 'view' list!)
	 *
	 * Expected parameters:
	 *
	 * $_GET['directory']      path relative to basedir a.k.a. options['directory'] root
	 *
	 * $_GET['file']           filename (including extension, of course) of the file to
	 *                         be thumbnailed.
	 *
	 * $_GET['size']           the requested thumbnail maximum width / height (the bounding box is square).
	 *                         Must be one of our 'authorized' sizes: 48, 250.
	 *
	 * $_GET['filter']         optional mimetype filter string, amy be the part up to and
	 *                         including the slash '/' or the full mimetype. Only files
	 *                         matching this (set of) mimetypes will be listed.
	 *                         Examples: 'image/' or 'application/zip'
	 *
	 * $_GET['asJSON']        return some JSON {status: 1, thumbnail: 'path/to/thumbnail.png' }
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                  0 for error; nonzero for success
	 *
	 * error                   error message
	 *
	 * Next to these, the JSON encoded output will, with high probability, include a
	 * list view of the parent or 'basedir' as a fast and easy fallback mechanism for client side
	 * viewing code. However, severe and repetitive errors may not produce this
	 * 'fallback view list' so proper client code should check the 'status' field in the
	 * JSON output.
	 */
	protected function onThumbnail()
	{
		// try to produce the view; if it b0rks, retry with the parent, until we've arrived at the basedir:
		// then we fail more severely.

		$emsg = null;
		$img_filepath = null;
		$reqd_size = 48;
		$filename = null;
		$as_JSON = false;
		$jserr = array(
				'status' => 1
			);

		try
		{
			$v_ex_code = 'disabled';

			$as_JSON = $this->getGETParam('asJSON', 0);

			$reqd_size = intval($this->getGETparam('size'));
			if (!empty($reqd_size))
			{
				// and when not requesting one of our 'authorized' thumbnail sizes, you're gonna burn as well!
				if (in_array($reqd_size, array(16, 48, 250)))
					$v_ex_code = null;
			}

			$file_arg = $this->getGETparam('file');

			$dir_arg = $this->getGETparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);

			$mime_filter = $this->getGETparam('filter', $this->options['filter']);
			$mime_filters = $this->getAllowedMimeTypes($mime_filter);

			$filename = null;
			$file = null;
			$mime = null;
			if (!empty($file_arg) && empty($v_ex_code))
			{
				$v_ex_code = 'nofile';

				$filename = pathinfo($file_arg, PATHINFO_BASENAME);
				$legal_url .= $filename;
				// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
				$file = $this->legal_url_path2file_path($legal_url);

				if (is_readable($file))
				{
					if (is_file($file))
					{
						$mime = $this->getMimeType($file);
						if ($this->IsAllowedMimeType($mime, $mime_filters))
							$v_ex_code = null;
					}
					else
					{
						$mime = 'text/directory';
					}
				}
			}
			else if (empty($v_ex_code))
			{
				$v_ex_code = 'nofile';
			}

			$fileinfo = array(
					'legal_url' => $legal_url,
					'file' => $file,
					'filename' => $filename,
					'mime' => $mime,
					'mime_filter' => $mime_filter,
					'mime_filters' => $mime_filters,
					'requested_size' => $reqd_size,
					'mode' => ($as_JSON ? 'json' : 'image'),
					'validation_failure' => $v_ex_code
				);

			if (!empty($this->options['ThumbnailIsAuthorized_cb']) && function_exists($this->options['ThumbnailIsAuthorized_cb']) && !$this->options['ThumbnailIsAuthorized_cb']($this, 'thumbnail', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			$file = $fileinfo['file'];
			$filename = $fileinfo['filename'];
			$mime = $fileinfo['mime'];
			$mime_filter = $fileinfo['mime_filter'];
			$mime_filters = $fileinfo['mime_filters'];
			$reqd_size = $fileinfo['requested_size'];
			$as_JSON = ($fileinfo['mode'] == 'json');

			/*
			 * each image we inspect may throw an exception due to an out of memory warning
			 * (which is far better than without those: a silent fatal abort!)
			 *
			 * However, now that we do have a way to check most memory failures occurring in here (due to large images
			 * and too little available RAM) we /still/ want to see that happen: for broken and overlarge images, we
			 * produce some alternative graphics instead!
			 */
			$thumb_path = null;
			if (FileManagerUtility::startsWith($mime, 'image/'))
			{
				// access the image and create a thumbnail image; this can fail dramatically
				$thumb_path = $this->getThumb($legal_url, $file, $reqd_size, $reqd_size);
			}

			$img_filepath = (!empty($thumb_path) ? $thumb_path : $this->getIcon($filename, $reqd_size <= 16));
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything and should only happen in the direst of circumstances, we don't bother translating
			$emsg = $e->getMessage();
		}

		// now go and serve the content of the thumbnail / icon image file (which we still need to determine /exactly/):
		try
		{
			if (!empty($emsg))
			{
				$img_filepath = $this->getIconForError($emsg, $filename, $reqd_size <= 16);
			}

			$file = $this->url_path2file_path($img_filepath);
			if (!$as_JSON)
			{
				$fd = fopen($file, 'rb');
				if (!$fd)
				{
					// when the icon / thumbnail cannot be opened for whatever reason, fall back to the default error image:
					$file = $this->url_path2file_path($this->getIcon('is.default-error', $reqd_size <= 16));
					$fd = fopen($file, 'rb');
					if (!$fd)
						throw new Exception('panic');
				}
				$mime = $this->getMimeType($file);
				$fsize = filesize($file);
				if (!empty($mime))
				{
					header('Content-Type: ' . $mime);
				}
				header('Content-Length: ' . $fsize);

				header("Cache-Control: private"); //use this to open files directly

				fpassthru($fd);
				fclose($fd);
				exit();
			}
			else if (file_exists($file))
			{
				$jserr['thumbnail'] = $img_filepath;
			}
		}
		catch(Exception $e)
		{
			if (!$as_JSON)
			{
				send_response_status_header(500);
				echo 'Cannot produce thumbnail: ' . $emsg . ' :: ' . $img_filepath;
			}
			$emsg = $e->getMessage();
		}

		$this->modify_json4exception($jserr, $emsg);

		if (!headers_sent()) header('Content-Type: application/json');

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode($jserr);
	}


	/**
	 * Process the 'destroy' event
	 *
	 * Delete the specified file or directory and return a JSON encoded status of success
	 * or failure.
	 *
	 * Note that when images are deleted, so are their thumbnails.
	 *
	 * Expected parameters:
	 *
	 * $_POST['directory']     path relative to basedir a.k.a. options['directory'] root
	 *
	 * $_POST['file']          filename (including extension, of course) of the file to
	 *                         be detailed.
	 *
	 * $_POST['filter']        optional mimetype filter string, amy be the part up to and
	 *                         including the slash '/' or the full mimetype. Only files
	 *                         matching this (set of) mimetypes will be listed.
	 *                         Examples: 'image/' or 'application/zip'
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                  0 for error; nonzero for success
	 *
	 * error                   error message
	 */
	protected function onDestroy()
	{
		$emsg = null;
		$jserr = array(
				'status' => 1
			);

		try
		{
			if (!$this->options['destroy'])
				throw new FileManagerException('disabled');

			$v_ex_code = 'nofile';

			$file_arg = $this->getPOSTparam('file');

			$dir_arg = $this->getPOSTparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);

			$mime_filter = $this->getPOSTparam('filter', $this->options['filter']);
			$mime_filters = $this->getAllowedMimeTypes($mime_filter);

			$filename = null;
			$file = null;
			$mime = null;
			if (!empty($file_arg))
			{
				$filename = pathinfo($file_arg, PATHINFO_BASENAME);
				$legal_url .= $filename;
				// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
				$file = $this->legal_url_path2file_path($legal_url);

				if (file_exists($file))
				{
					if (is_file($file))
					{
						$mime = $this->getMimeType($file);
						if ($this->IsAllowedMimeType($mime, $mime_filters))
							$v_ex_code = null;
					}
					else if (is_dir($file))
					{
						$mime = 'text/directory';
						$v_ex_code = null;
					}
				}
			}

			$fileinfo = array(
					'legal_url' => $legal_url,
					'file' => $file,
					'mime' => $mime,
					'mime_filter' => $mime_filter,
					'mime_filters' => $mime_filters,
					'validation_failure' => $v_ex_code
				);

			if (!empty($this->options['DestroyIsAuthorized_cb']) && function_exists($this->options['DestroyIsAuthorized_cb']) && !$this->options['DestroyIsAuthorized_cb']($this, 'destroy', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			$file = $fileinfo['file'];
			$mime = $fileinfo['mime'];
			$mime_filter = $fileinfo['mime_filter'];
			$mime_filters = $fileinfo['mime_filters'];

			if (!$this->unlink($legal_url, $mime_filters))
				throw new FileManagerException('unlink_failed:' . $legal_url);

			if (!headers_sent()) header('Content-Type: application/json');

			echo json_encode(array(
					'status' => 1,
					'content' => 'destroyed'
				));
			return;
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything and should only happen in the direst of circumstances, we don't bother translating
			$emsg = $e->getMessage();
		}

		$this->modify_json4exception($jserr, $emsg);

		if (!headers_sent()) header('Content-Type: application/json');

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode($jserr);
	}

	/**
	 * Process the 'create' event
	 *
	 * Create the specified subdirectory and give it the configured permissions
	 * (options['chmod'], default 0777) and return a JSON encoded status of success
	 * or failure.
	 *
	 * Expected parameters:
	 *
	 * $_POST['directory']     path relative to basedir a.k.a. options['directory'] root
	 *
	 * $_POST['file']          name of the subdirectory to be created
	 *
	 * Extra input parameters considered while producing the JSON encoded directory view.
	 * These may not seem relevant for an empty directory, but these parameters are also
	 * considered when providing the fallback directory view in case an error occurred
	 * and then the listed directory (either the parent or the basedir itself) may very
	 * likely not be empty!
	 *
	 * $_POST['filter']        optional mimetype filter string, amy be the part up to and
	 *                         including the slash '/' or the full mimetype. Only files
	 *                         matching this (set of) mimetypes will be listed.
	 *                         Examples: 'image/' or 'application/zip'
	 *
	 * $_POST['type']          'thumb' will produce a list view including thumbnail and other
	 *                         information with each listed file; other values will produce
	 *                         a basic list view (similar to Windows Explorer 'list' view).
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                  0 for error; nonzero for success
	 *
	 * error                   error message
	 */
	protected function onCreate()
	{
		$emsg = null;
		$jserr = array(
				'status' => 1
			);

		$mime_filter = $this->getPOSTparam('filter', $this->options['filter']);
		$list_type = ($this->getPOSTparam('type') != 'thumb' ? 'list' : 'thumb');

		$legal_url = null;

		try
		{
			if (!$this->options['create'])
				throw new FileManagerException('disabled');

			$v_ex_code = 'nofile';

			$dir_arg = $this->getPOSTparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);

			// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
			$dir = $this->legal_url_path2file_path($legal_url);

			$file_arg = $this->getPOSTparam('file');

			$filename = null;
			$file = null;
			$newdir = null;
			if (!empty($file_arg))
			{
				$filename = pathinfo($file_arg, PATHINFO_BASENAME);

				if (is_dir($dir))
				{
					$file = $this->getUniqueName(array('filename' => $filename), $dir);  // a directory has no 'extension'!
					if ($file)
					{
						$newdir = $this->legal_url_path2file_path($legal_url . $file);
						$v_ex_code = null;
					}
				}
			}

			$fileinfo = array(
					'legal_url' => $legal_url,
					'dir' => $dir,
					'raw_name' => $filename,
					'uniq_name' => $file,
					'newdir' => $newdir,
					'chmod' => $this->options['chmod'],
					'validation_failure' => $v_ex_code
				);
			if (!empty($this->options['CreateIsAuthorized_cb']) && function_exists($this->options['CreateIsAuthorized_cb']) && !$this->options['CreateIsAuthorized_cb']($this, 'create', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			$dir = $fileinfo['dir'];
			$filename = $fileinfo['raw_name'];
			$file = $fileinfo['uniq_name'];
			$newdir = $fileinfo['newdir'];

			if (!@mkdir($newdir, $fileinfo['chmod'], true))
				throw new FileManagerException('mkdir_failed:' . $this->legal2abs_url_path($legal_url) . $file);

			if (!headers_sent()) header('Content-Type: application/json');

			// success, now show the new directory as a list view:
			$rv = $this->_onView($legal_url . $file . '/', $jserr, $mime_filter, $list_type);
			echo json_encode($rv);
			return;
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();

			$jserr['status'] = 0;

			// and fall back to showing the PARENT directory
			try
			{
				$jserr = $this->_onView($legal_url, $jserr, $mime_filter, $list_type);
			}
			catch (Exception $e)
			{
				// and fall back to showing the BASEDIR directory
				try
				{
					$legal_url = $this->options['directory'];
					$jserr = $this->_onView($legal_url, $jserr, $mime_filter, $list_type);
				}
				catch (Exception $e)
				{
					// when we fail here, it's pretty darn bad and nothing to it.
					// just push the error JSON as go.
				}
			}
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything and should only happen in the direst of circumstances, we don't bother translating
			$emsg = $e->getMessage();

			$jserr['status'] = 0;

			// and fall back to showing the PARENT directory
			try
			{
				$jserr = $this->_onView($legal_url, $jserr, $mime_filter, $list_type);
			}
			catch (Exception $e)
			{
				// and fall back to showing the BASEDIR directory
				try
				{
					$legal_url = $this->options['directory'];
					$jserr = $this->_onView($legal_url, $jserr, $mime_filter, $list_type);
				}
				catch (Exception $e)
				{
					// when we fail here, it's pretty darn bad and nothing to it.
					// just push the error JSON as go.
				}
			}
		}

		$this->modify_json4exception($jserr, $emsg);

		if (!headers_sent()) header('Content-Type: application/json');

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode($jserr);
	}

	/**
	 * Process the 'download' event
	 *
	 * Send the file content of the specified file for download by the client.
	 * Only files residing within the directory tree rooted by the
	 * 'basedir' (options['directory']) will be allowed to be downloaded.
	 *
	 * Expected parameters:
	 *
	 * $_GET['file']          filepath of the file to be downloaded
	 *
	 * $_GET['filter']        optional mimetype filter string, amy be the part up to and
	 *                        including the slash '/' or the full mimetype. Only files
	 *                        matching this (set of) mimetypes will be listed.
	 *                        Examples: 'image/' or 'application/zip'
	 *
	 * On errors a HTTP 403 error response will be sent instead.
	 */
	protected function onDownload()
	{
		try
		{
			if (!$this->options['download'])
				throw new FileManagerException('disabled');

			$v_ex_code = 'nofile';

			$file_arg = $this->getGETparam('file');

			$mime_filter = $this->getGETparam('filter', $this->options['filter']);
			$mime_filters = $this->getAllowedMimeTypes($mime_filter);

			$legal_url = null;
			$file = null;
			$mime = null;
			if (!empty($file_arg))
			{
				$legal_url = $this->rel2abs_legal_url_path($file_arg);
				//$legal_url = self::enforceTrailingSlash($legal_url);

				// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
				$file = $this->legal_url_path2file_path($legal_url);

				if (is_readable($file))
				{
					if (is_file($file))
					{
						$mime = $this->getMimeType($file);
						if ($this->IsAllowedMimeType($mime, $mime_filters))
							$v_ex_code = null;
					}
					else
					{
						$mime = 'text/directory';
					}
				}
			}

			$fileinfo = array(
					'legal_url' => $legal_url,
					'file' => $file,
					'mime' => $mime,
					'mime_filter' => $mime_filter,
					'mime_filters' => $mime_filters,
					'validation_failure' => $v_ex_code
				);
			if (!empty($this->options['DownloadIsAuthorized_cb']) && function_exists($this->options['DownloadIsAuthorized_cb']) && !$this->options['DownloadIsAuthorized_cb']($this, 'download', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			$file = $fileinfo['file'];
			$mime = $fileinfo['mime'];
			$mime_filter = $fileinfo['mime_filter'];
			$mime_filters = $fileinfo['mime_filters'];

			if ($fd = fopen($file, 'rb'))
			{
				$fsize = filesize($file);
				$path_parts = pathinfo($legal_url);
				$ext = strtolower($path_parts["extension"]);
				// see also: http://www.boutell.com/newfaq/creating/forcedownload.html
				switch ($ext)
				{
				case "pdf":
					header('Content-Type: application/pdf');
					break;

				// add here more headers for diff. extensions

				default:
					header('Content-Type: application/octet-stream');
					break;
				}
				header('Content-Disposition: attachment; filename="' . $path_parts["basename"] . '"'); // use 'attachment' to force a download
				header("Content-length: $fsize");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false); // use this to open files directly

				fpassthru($fd);
				fclose($fd);
			}
		}
		catch(FileManagerException $e)
		{
			// we don't care whether it's a 404, a 403 or something else entirely: we feed 'em a 403 and that's final!
			send_response_status_header(403);
			echo $e->getMessage();
		}
		catch(Exception $e)
		{
			// we don't care whether it's a 404, a 403 or something else entirely: we feed 'em a 403 and that's final!
			send_response_status_header(403);
			echo $e->getMessage();
		}
	}

	/**
	 * Process the 'upload' event
	 *
	 * Process and store the uploaded file in the designated location.
	 * Images will be resized when possible and applicable. A thumbnail image will also
	 * be preproduced when possible.
	 * Return a JSON encoded status of success or failure.
	 *
	 * Expected parameters:
	 *
	 * $_GET['directory']     path relative to basedir a.k.a. options['directory'] root
	 *
	 * $_GET['resize']        nonzero value indicates any uploaded image should be resized to the configured options['maxImageDimension'] width and height whenever possible
	 *
	 * $_GET['filter']        optional mimetype filter string, amy be the part up to and
	 *                        including the slash '/' or the full mimetype. Only files
	 *                        matching this (set of) mimetypes will be listed.
	 *                        Examples: 'image/' or 'application/zip'
	 *
	 * $_FILES[]              the metadata for the uploaded file
	 *
	 * $_GET['reportContentType'] if you want a specific content type header set on our response, put it here.
	 *                        This is needed for when we are posting an upload response to a hidden iframe, the
	 *                        default application/json mimetype breaks down in that case at least for Firefox 3.X
	 *                        as the browser will pop up a save/view dialog before JS can access the transmitted data.
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                 0 for error; nonzero for success
	 *
	 * error                  error message
	 */
	protected function onUpload()
	{
		$emsg = null;
		$jserr = array(
				'status' => 1
			);

		try
		{
			if (!$this->options['upload'])
				throw new FileManagerException('disabled');

			// MAY upload zero length files!
			if (!isset($_FILES) || empty($_FILES['Filedata']) || empty($_FILES['Filedata']['name']))
				throw new FileManagerException('nofile');

			$v_ex_code = 'nofile';

			$file_size = (empty($_FILES['Filedata']['size']) ? 0 : $_FILES['Filedata']['size']);

			$file_arg = $_FILES['Filedata']['name'];

			$dir_arg = $this->getGETparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);
			// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
			$dir = $this->legal_url_path2file_path($legal_url);

			$mime_filter = $this->getGETparam('filter', $this->options['filter']);
			$mime_filters = $this->getAllowedMimeTypes($mime_filter);

			$tmppath = $_FILES['Filedata']['tmp_name'];

			$filename = null;
			$fi = array('filename' => null, 'extension' => null);
			$mime = null;
			if (!empty($file_arg))
			{
				$filename = $this->getUniqueName($file_arg, $dir);
				if (!empty($filename))
				{
					$fi = pathinfo($filename);

					$mime = $this->getMimeType($tmppath);
					if (!$this->IsAllowedMimeType($mime, $mime_filters))
					{
						$v_ex_code = 'extension';
					}
					else
					{
						/*
						Security:

						Upload::move() processes the unfiltered version of $_FILES[]['name'], at least to get the extension,
						unless we ALWAYS override the filename and extension in the options array below. That's why we
						calculate the extension at all times here.
						*/
						if (!is_string($fi['extension']) || strlen($fi['extension']) == 0) // can't use 'empty()' as "0" is a valid extension itself.
						{
							//enforce a mandatory extension, even when there isn't one (due to filtering or original input producing none)
							$fi['extension'] = 'txt';
						}
						else if ($this->options['safe'] && in_array(strtolower($fi['extension']), array('exe', 'dll', 'com', 'php', 'php3', 'php4', 'php5', 'phps')))
						{
							$fi['extension'] = 'txt';
						}
						$v_ex_code = null;
					}
				}
			}

			$fileinfo = array(
				'legal_url' => $legal_url,
				'dir' => $dir,
				'raw_filename' => $file_arg,
				'name' => $fi['filename'],
				'extension' => $fi['extension'],
				'mime' => $mime,
				'mime_filter' => $mime_filter,
				'mime_filters' => $mime_filters,
				'tmp_filepath' => $tmppath,
				'size' => $file_size,
				'maxsize' => $this->options['maxUploadSize'],
				'overwrite' => false,
				'chmod' => $this->options['chmod'] & 0666,   // security: never make those files 'executable'!
				'validation_failure' => $v_ex_code,
				'mime_valid_check' => $this->IsAllowedMimeType($mime, $mime_filters),
				'image_info' => @getimagesize($tmppath)
			);
			if (!empty($this->options['UploadIsAuthorized_cb']) && function_exists($this->options['UploadIsAuthorized_cb']) && !$this->options['UploadIsAuthorized_cb']($this, 'upload', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			$dir = $fileinfo['dir'];
			$file_arg = $fileinfo['raw_filename'];
			$filename = $fileinfo['name'] . (!empty($fileinfo['extension']) ? '.' . $fileinfo['extension'] : '');
			$mime = $fileinfo['mime'];
			$mime_filter = $fileinfo['mime_filter'];
			$mime_filters = $fileinfo['mime_filters'];
			//$tmppath = $fileinfo['tmp_filepath'];

			if($fileinfo['maxsize'] && $fileinfo['size'] > $fileinfo['maxsize'])
				throw new FileManagerException('size');

			if(!$fileinfo['extension'])
				throw new FileManagerException('extension');

			// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
			$file = $this->legal_url_path2file_path($legal_url . $filename);

			if(!$fileinfo['overwrite'] && file_exists($file))
				throw new FileManagerException('exists');

			if(!move_uploaded_file($_FILES['Filedata']['tmp_name'], $file))
				throw new FileManagerException(strtolower($_FILES['Filedata']['error'] <= 2 ? 'size' : ($_FILES['Filedata']['error'] == 3 ? 'partial' : 'path')));

			@chmod($file, $fileinfo['chmod']);


			/*
			 * NOTE: you /can/ (and should be able to, IMHO) upload 'overly large' image files to your site, but the resizing process step
			 *       happening here will fail; we have memory usage estimators in place to make the fatal crash a non-silent one, i,e, one
			 *       where we still have a very high probability of NOT fatally crashing the PHP iunterpreter but catching a suitable exception
			 *       instead.
			 *       Having uploaded such huge images, a developer/somebody can always go in later and up the memory limit if the site admins
			 *       feel it is deserved. Until then, no thumbnails of such images (though you /should/ be able to milkbox-view the real thing!)
			 */
			if (FileManagerUtility::startsWith($mime, 'image/') && $this->getGETparam('resize', 0))
			{
				$img = new Image($file);
				$size = $img->getSize();
				// Image::resize() takes care to maintain the proper aspect ratio, so this is easy
				// (default quality is 100% for JPEG so we get the cleanest resized images here)
				$img->resize($this->options['maxImageDimension']['width'], $this->options['maxImageDimension']['height'])->save();
				unset($img);
			}

			if (!headers_sent()) header('Content-Type: ' . $this->getGetparam('reportContentType', 'application/json'));

			echo json_encode(array(
					'status' => 1,
					'name' => pathinfo($file, PATHINFO_BASENAME)
				));
			return;
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything and should only happen in the direst of circumstances, we don't bother translating
			$emsg = $e->getMessage();
		}

		$this->modify_json4exception($jserr, $emsg);

		if (!headers_sent()) header('Content-Type: ' . $this->getGetparam('reportContentType', 'application/json'));

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode(array_merge($jserr, $_FILES));
	}

	/**
	 * Process the 'move' event (with is used by both move/copy and rename client side actions)
	 *
	 * Copy or move/rename a given file or directory and return a JSON encoded status of success
	 * or failure.
	 *
	 * Expected parameters:
	 *
	 * $_POST['copy']            nonzero value means copy, zero or nil for move/rename
	 *
	 * Source filespec:
	 *
	 *   $_POST['directory']     path relative to basedir a.k.a. options['directory'] root
	 *
	 *   $_POST['file']          original name of the file/subdirectory to be renamed/copied
	 *
	 * Destination filespec:
	 *
	 *   $_POST['newDirectory']  path relative to basedir a.k.a. options['directory'] root;
	 *                           target directory where the file must be moved / copied
	 *
	 *   $_POST['name']          target name of the file/subdirectory to be renamed
	 *
	 * Errors will produce a JSON encoded error report, including at least two fields:
	 *
	 * status                    0 for error; nonzero for success
	 *
	 * error                     error message
	 */
	protected function onMove()
	{
		$emsg = null;
		$jserr = array(
				'status' => 1
			);

		try
		{
			if (!$this->options['move'])
				throw new FileManagerException('disabled');

			$v_ex_code = 'nofile';

			$file_arg = $this->getPOSTparam('file');

			$dir_arg = $this->getPOSTparam('directory');
			$legal_url = $this->rel2abs_legal_url_path($dir_arg);
			$legal_url = self::enforceTrailingSlash($legal_url);

			// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
			$dir = $this->legal_url_path2file_path($legal_url);

			$newdir_arg = $this->getPOSTparam('newDirectory');
			$newname_arg = $this->getPOSTparam('name');
			$rename = (empty($newdir_arg) && !empty($newname_arg));

			$is_copy = !!$this->getPOSTparam('copy');

			$filename = null;
			$path = null;
			$fn = null;
			$legal_newurl = null;
			$newdir = null;
			$newname = null;
			$newpath = null;
			$is_dir = false;
			if (!empty($file_arg))
			{
				$filename = pathinfo($file_arg, PATHINFO_BASENAME);
				$path = $this->legal_url_path2file_path($legal_url . $filename);

				if (file_exists($path))
				{
					$is_dir = is_dir($path);

					// note: we do not support copying entire directories, though directory rename/move is okay
					if ($is_copy && $is_dir)
					{
						$v_ex_code = 'disabled';
					}
					else if ($rename)
					{
						$fn = 'rename';
						$legal_newurl = $legal_url;
						$newdir = $dir;

						$newname = pathinfo($newname_arg, PATHINFO_BASENAME);
						if ($is_dir)
							$newname = $this->getUniqueName(array('filename' => $newname), $newdir);  // a directory has no 'extension'
						else
							$newname = $this->getUniqueName($newname, $newdir);

						if (!$newname)
						{
							$v_ex_code = 'nonewfile';
						}
						else
						{
							// when the new name seems to have a different extension, make sure the extension doesn't change after all:
							// Note: - if it's only 'case' we're changing here, then exchange the extension instead of appending it.
							//       - directories do not have extensions
							$extOld = pathinfo($filename, PATHINFO_EXTENSION);
							$extNew = pathinfo($newname, PATHINFO_EXTENSION);
							if ((!$this->options['allowExtChange'] || (!$is_dir && empty($extNew))) && !empty($extOld) && strtolower($extOld) != strtolower($extNew))
							{
								$newname .= '.' . $extOld;
							}
							$v_ex_code = null;
						}
					}
					else
					{
						$fn = ($is_copy ? 'copy' : 'rename' /* 'move' */);
						$legal_newurl = $this->rel2abs_legal_url_path($newdir_arg);
						$legal_newurl = self::enforceTrailingSlash($legal_newurl);
						$newdir = $this->legal_url_path2file_path($legal_newurl);

						if ($is_dir)
							$newname = $this->getUniqueName(array('filename' => $filename), $newdir);  // a directory has no 'extension'
						else
							$newname = $this->getUniqueName($filename, $newdir);

						if (!$newname)
							$v_ex_code = 'nonewfile';
						else
							$v_ex_code = null;
					}

					if (empty($v_ex_code))
					{
						$newpath = $this->legal_url_path2file_path($legal_newurl . $newname);
					}
				}
			}

			$fileinfo = array(
					'legal_url' => $legal_url,
					'dir' => $dir,
					'path' => $path,
					'name' => $filename,
					'legal_newurl' => $legal_newurl,
					'newdir' => $newdir,
					'newpath' => $newpath,
					'newname' => $newname,
					'rename' => $rename,
					'is_dir' => $is_dir,
					'function' => $fn,
					'validation_failure' => $v_ex_code
				);

			if (!empty($this->options['MoveIsAuthorized_cb']) && function_exists($this->options['MoveIsAuthorized_cb']) && !$this->options['MoveIsAuthorized_cb']($this, 'move', $fileinfo))
			{
				$v_ex_code = $fileinfo['validation_failure'];
				if (empty($v_ex_code)) $v_ex_code = 'authorized';
			}
			if (!empty($v_ex_code))
				throw new FileManagerException($v_ex_code);

			$legal_url = $fileinfo['legal_url'];
			$dir = $fileinfo['dir'];
			$path = $fileinfo['path'];
			$filename = $fileinfo['name'];
			$legal_newurl = $fileinfo['legal_newurl'];
			$newdir = $fileinfo['newdir'];
			$newpath = $fileinfo['newpath'];
			$newname = $fileinfo['newname'];
			$rename = $fileinfo['rename'];
			$is_dir = $fileinfo['is_dir'];
			$fn = $fileinfo['function'];

			if($rename)
			{
				// try to remove the thumbnail related to the original file; don't mind if it doesn't exist
				if(!$is_dir)
				{
					if (!$this->deleteThumb($legal_url . $filename))
						throw new FileManagerException('delete_thumbnail_failed');
				}
			}

			if (!function_exists($fn))
				throw new FileManagerException((empty($fn) ? 'rename' : $fn) . '_failed:' . $legal_newurl . ':' . $newname);
			if (!@$fn($path, $newpath))
				throw new FileManagerException($fn . '_failed:' . $legal_newurl . ':' . $newname);

			if (!headers_sent()) header('Content-Type: application/json');

			echo json_encode(array(
				'status' => 1,
				'name' => $newname
			));
			return;
		}
		catch(FileManagerException $e)
		{
			$emsg = $e->getMessage();
		}
		catch(Exception $e)
		{
			// catching other severe failures; since this can be anything and should only happen in the direst of circumstances, we don't bother translating
			$emsg = $e->getMessage();
		}

		$this->modify_json4exception($jserr, $emsg);

		if (!headers_sent()) header('Content-Type: application/json');

		// when we fail here, it's pretty darn bad and nothing to it.
		// just push the error JSON as go.
		echo json_encode($jserr);
	}







	/**
	 * Convert a given file spec into a URL pointing at our JiT thumbnail creation/delivery event handler.
	 *
	 * The spec must be an array with these elements:
	 *   'event':       'thumbnail'
	 *   'directory':   URI path to directory of the ORIGINAL file
	 *   'file':        filename of the ORIGINAL file
	 *   'size':        requested thumbnail size (e.g. 48)
	 *   'filter':      optional mime_filter as originally specified by the client
	 *   'type':        'thumb' or 'list': the current type of directory view at the client
	 *
	 * Return the URL string.
	 */
	public function mkEventHandlerURL($spec)
	{
		// first determine how the client can reach us; assume that's the same URI as he went to right now.
		$our_handler_url = $this->getRequestScriptURI();

		if (is_array($this->options['URIpropagateData']))
		{
			// the items in 'spec' always win over any entries in 'URIpropagateData':
			$spec = array_merge(array(), $this->options['URIpropagateData'], $spec);
		}

		// next, construct the query part of the URI:
		$qstr = http_build_query_ex($spec, null, '&', null, PHP_QUERY_RFC3986);

		return $our_handler_url . '?' . $qstr;
	}



	/**
	 * Produce a HTML snippet detailing the given file in the JSON 'content' element; place additional info
	 * in the JSON elements 'thumbnail', 'thumbnail48', 'thumbnail250', 'width', 'height', ...
	 *
	 * Return an augmented JSON array.
	 *
	 * Throw an exception on error.
	 */
	public function extractDetailInfo($json, $legal_url, $mime_filter, $mime_filters)
	{
		$url = $this->legal2abs_url_path($legal_url);
		$filename = pathinfo($url, PATHINFO_BASENAME);

		// must transform here so alias/etc. expansions inside url_path2file_path() get a chance:
		$file = $this->url_path2file_path($url);

		$isdir = !is_file($file);
		$bad_ext = false;
		$mime = null;
		$fi = null;
		if (!$isdir)
		{
			$fi = $this->getFileInfo($file);
			if (!empty($fi['mime_type']))
				$mime = $fi['mime_type'];
			if (empty($mime))
				$mime = 'application/octet-stream';
			//$mime = $this->getMimeType($file);

			$mime2 = $this->getMimeType($file, true);
			$fi['mime_type from file extension'] = $mime2;
			$bad_ext = ($mime2 != $mime);
			if ($bad_ext)
			{
				$iconspec = 'is.' + $this->getExtFromMime($mime);
			}
			else
			{
				$iconspec = $filename;
			}

			if (!$this->IsAllowedMimeType($mime, $mime_filters))
				throw new FileManagerException('extension');
		}
		else if (is_dir($file))
		{
			$mime = 'text/directory';
			$iconspec = 'is.dir';
		}
		else
		{
			// simply do NOT list anything that we cannot cope with.
			// That includes clearly inaccessible files (and paths) with non-ASCII characters:
			// PHP5 and below are a real mess when it comes to handling Unicode filesystems
			// (see the php.net site too: readdir / glob / etc. user comments and the official
			// notice that PHP will support filesystem UTF-8/Unicode only when PHP6 is released.
			//
			// Big, fat bummer!
			throw new FileManagerException('nofile');
		}

		$thumbnail = $this->getIcon($iconspec, false);
		$thumb48 = FileManagerUtility::rawurlencode_path($thumbnail);
		$thumb250 = $thumb48;
		$icon = $this->getIcon($iconspec, true);

		$json = array_merge(array(
				//'status' => 1,
				//'mimetype' => $mime,
				'content' => self::compressHTML('<div class="margin">
					${nopreview}
				</div>')
			),
			(is_array($json) ? $json : array()),
			array(
				'path' => FileManagerUtility::rawurlencode_path($url),
				'name' => $filename,
				'date' => date($this->options['dateFormat'], @filemtime($file)),
				'mime' => $mime,
				//'thumbnail' => $thumbnail,
				'thumbnail48' => $thumb48,
				'thumbnail250' => $thumb250,
				'icon' => FileManagerUtility::rawurlencode_path($icon),
				'size' => @filesize($file)
			));


		$content_classes = "margin" . ($bad_ext ? ' preview_bad_mime' : '');
		$content = '';
		$preview_HTML = null;
		$postdiag_HTML = '';

		$mime_els = explode('/', $mime);
		for(;;) // bogus loop; only meant to assist the [mime remapping] state machine in here
		{
			switch ($mime_els[0])
			{
			case 'image':
				// generates a random number to put on the end of the image, to prevent caching
				//$randomImage = '?'.md5(uniqid(rand(),1));

				//$size = @getimagesize($file);
				//// check for badly formatted image files (corruption); we'll handle the overly large ones next
				//if (!$size)
				//  throw new FileManagerException('corrupt_img:' . $url);

				/*
				 * offload the thumbnailing process to another event ('event=thumbnail') to be fired by the client
				 * when it's time to render the thumbnail: the offloading helps us tremendously in coping with large
				 * directories:
				 * WE simply assume the thumbnail will be there, so we don't even need to check for its existence
				 * (which saves us one more file_exists() per item at the very least). And when it doesn't, that's
				 * for the event=thumbnail handler to worry about (creating the thumbnail on demand or serving
				 * a generic icon image instead).
				 */
				$thumb48 = false;
				$thumb250 = false;
				$meta = null;
				try
				{
					if (!$this->options['thumbnailsMustGoThroughBackend'])
					{
						$thumb48  = $this->getThumb($url, $file, 48, 48, true);
						$thumb250 = $this->getThumb($url, $file, 250, 250, true);
					}

					if ($thumb48 === false || $thumb250 === false)
					{
						/*
						 * do NOT generate the thumbnail itself yet (it takes too much time!) but do check whether it CAN be generated
						 * at all: THAT is a (relatively speaking) fast operation!
						 */
						$meta = Image::checkFileForProcessing($file);
					}
					if ($thumb48 === false)
					{
						$thumb48 = $this->mkEventHandlerURL(array(
								'event' => 'thumbnail',
								// directory and filename of the ORIGINAL image should follow next:
								'directory' => pathinfo($legal_url, PATHINFO_DIRNAME),
								'file' => pathinfo($legal_url, PATHINFO_BASENAME),
								'size' => 48,          // thumbnail suitable for 'view/type=thumb' list views
								'filter' => $mime_filter
							));
					}
					else
					{
						$thumb48 = FileManagerUtility::rawurlencode_path($thumb48);
					}
					if ($thumb250 === false)
					{
						$thumb250 = $this->mkEventHandlerURL(array(
								'event' => 'thumbnail',
								// directory and filename of the ORIGINAL image should follow next:
								'directory' => pathinfo($legal_url, PATHINFO_DIRNAME),
								'file' => pathinfo($legal_url, PATHINFO_BASENAME),
								'size' => 250,         // thumbnail suitable for 'view/type=thumb' list views
								'filter' => $mime_filter
							));
					}
					else
					{
						$thumb250 = FileManagerUtility::rawurlencode_path($thumb250);
					}

					// get the size of the thumbnail/icon: the <img> is styled with width and height to ensure the background 'loader' image is shown correctly:
					//$tnpath = $this->url_path2file_path($thumbfile);
					//$tninf = @getimagesize($tnpath);

					//$json['tn_width'] = $tninf[0];
					//$json['tn_height'] = $tninf[1];

				}
				catch (Exception $e)
				{
					$emsg = $e->getMessage();
					$thumb48 = FileManagerUtility::rawurlencode_path($this->getIconForError($emsg, $legal_url, false));
					$thumb250 = $thumb48;
				}

				$json['thumbnail48'] = $thumb48;
				$json['thumbnail250'] = $thumb250;

				$sw_make = $this->getID3infoItem($fi, null, 'jpg', 'exif', 'IFD0', 'Software');
				$time_make = $this->getID3infoItem($fi, null, 'jpg', 'exif', 'IFD0', 'DateTime');

				$width = $this->getID3infoItem($fi, 0, 'video', 'resolution_x');
				$height = $this->getID3infoItem($fi, 0, 'video', 'resolution_y');
				$json['width'] = $width;
				$json['height'] = $height;

				$content = '<dl>
						<dt>${width}</dt><dd>' . $width . 'px</dd>
						<dt>${height}</dt><dd>' . $height . 'px</dd>
					</dl>';
				if (!empty($sw_make) || !empty($time_make))
				{
					$content .= '<p>Made with ' . (empty($sw_make) ? '???' : $sw_make) . ' @ ' . (empty($time_make) ? '???' : $time_make) . '</p>';
				}

				$enc_thumbfile = $thumb250;

				$preview_HTML = '<a href="' . FileManagerUtility::rawurlencode_path($url) . '" data-milkbox="single" title="' . htmlentities($filename, ENT_QUOTES, 'UTF-8') . '">
							   <img src="' . $enc_thumbfile . '" class="preview" alt="preview" />
							 </a>';
				if (!empty($emsg))
				{
					// use the abilities of modify_json4exception() to munge/format the exception message:
					$jsa = array();
					$this->modify_json4exception($jsa, $emsg);
					$postdiag_HTML .= "\n" . '<p class="err_info">' . $jsa['error'] . '</p>';
				}
				if (!empty($emsg) && strpos($emsg, 'img_will_not_fit') !== false)
				{
					$earr = explode(':', $e->getMessage(), 2);
					$postdiag_HTML .= "\n" . '<p class="tech_info">Estimated minimum memory requirements to create thumbnails for this image: ' . $earr[1] . '</p>';
				}

				$exif_data = $this->getID3infoItem($fi, null, 'jpg', 'exif');
				try
				{
					if (!empty($exif_data))
					{
						/*
						 * before dumping the EXIF data array (which may carry binary content and MAY CRASH the json_encode()r >:-((
						 * we filter it to prevent such crashes and oddly looking (diagnostic) presentation of values.
						 */
						self::clean_EXIF_results($exif_data);
						ob_start();
							var_dump($exif_data);
						$dump = ob_get_clean();
						$postdiag_HTML .= $dump;
					}
				}
				catch (Exception $e)
				{
					// use the abilities of modify_json4exception() to munge/format the exception message:
					$jsa = array('error' => '');
					$this->modify_json4exception($jsa, $e->getMessage());
					$postdiag_HTML .= "\n" . '<p class="err_info">' . $jsa['error'] . '</p>';
				}
				break;

			case 'text':
				switch ($mime_els[1])
				{
				case 'directory':
					$preview_HTML = '';
					break;

				default:
					// text preview:
					$filecontent = @file_get_contents($file, false, null, 0);
					if ($filecontent === false)
						throw new FileManagerException('nofile');

					if (!FileManagerUtility::isBinary($filecontent))
					{
						$content_classes .= ' textpreview';
						$preview_HTML = '<pre>' . str_replace(array('$', "\t"), array('&#36;', '&nbsp;&nbsp;'), htmlentities($filecontent, ENT_NOQUOTES, 'UTF-8')) . '</pre>';
					}
					else
					{
						// else: fall back to 'no preview available' (if getID3 didn't deliver instead...)
						$mime_els[0] = 'unknown'; // remap!
						continue;
					}
					break;
				}
				break;

			case 'application':
				switch ($mime_els[1])
				{
				case 'x-javascript':
					$mime_els[0] = 'text'; // remap!
					continue;

				case 'zip':
					$out = array(array(), array());
					$info = $this->getID3infoItem($fi, null, 'zip', 'files');
					if (is_array($info))
					{
						foreach ($info as $name => $size)
						{
							$isdir = is_array($size) ? true : false;
							$out[($isdir) ? 0 : 1][$name] = '<li><a><img src="' . FileManagerUtility::rawurlencode_path($this->getIcon($name, true)) . '" alt="" /> ' . $name . '</a></li>';
						}
						natcasesort($out[0]);
						natcasesort($out[1]);
						$preview_HTML = '<ul>' . implode(array_merge($out[0], $out[1])) . '</ul>';
					}
					break;

				case 'x-shockwave-flash':
					$info = $this->getID3infoItem($fi, null, 'swf', 'header');
					if (is_array($info))
					{
						// Note: preview data= urls were formatted like this in CCMS:
						// $this->options['assetBasePath'] . 'dewplayer.swf?mp3=' . rawurlencode($url) . '&volume=30'

						$width = $this->getID3infoItem($fi, 0, 'swf', 'header', 'frame_width') / 10;
						$height = $this->getID3infoItem($fi, 0, 'swf', 'header', 'frame_height') / 10;
						$json['width'] = $width;
						$json['height'] = $height;

						$content = '<dl>
								<dt>${width}</dt><dd>' . $width . 'px</dd>
								<dt>${height}</dt><dd>' . $height . 'px</dd>
								<dt>${length}</dt><dd>' . round($this->getID3infoItem($fi, 0, 'swf', 'header', 'length') / $this->getID3infoItem($fi, 25, 'swf', 'header', 'frame_count')) . 's</dd>
							</dl>';
					}
					break;

				default:
					// else: fall back to 'no preview available' (if getID3 didn't deliver instead...)
					$mime_els[0] = 'unknown'; // remap!
					continue;
				}
				break;

			case 'audio':
				$dewplayer = FileManagerUtility::rawurlencode_path($this->options['assetBasePath'] . 'dewplayer.swf');

				$content = '<dl>
						<dt>${title}</dt><dd>' . $this->getID3infoItem($fi, '???', 'comments', 'title', 0) . '</dd>
						<dt>${artist}</dt><dd>' . $this->getID3infoItem($fi, '???', 'comments', 'artist', 0) . '</dd>
						<dt>${album}</dt><dd>' . $this->getID3infoItem($fi, '???', 'comments', 'album', 0) . '</dd>
						<dt>${length}</dt><dd>' . $this->getID3infoItem($fi, '???', 'playtime_string') . '</dd>
						<dt>${bitrate}</dt><dd>' . round($this->getID3infoItem($fi, 0, 'bitrate') / 1000) . 'kbps</dd>
					</dl>';
				break;

			case 'video':
				$dewplayer = FileManagerUtility::rawurlencode_path($this->options['assetBasePath'] . 'dewplayer.swf');

				$a_fmt = $this->getID3infoItem($fi, '???', 'audio', 'dataformat');
				$a_samplerate = $this->getID3infoItem($fi, 0, 'audio', 'sample_rate') / 1000;
				$a_bitrate = round($this->getID3infoItem($fi, 0, 'audio', 'bitrate') / 1000);
				$a_bitrate_mode = $this->getID3infoItem($fi, '???', 'audio', 'bitrate_mode');
				$a_channels = $this->getID3infoItem($fi, 0, 'audio', 'channels');
				$a_codec = $this->getID3infoItem($fi, '', 'audio', 'codec');
				$a_streams = $this->getID3infoItem($fi, '???', 'audio', 'streams');
				$a_streamcount = (is_array($a_streams) ? count($a_streams) : 0);

				$v_fmt = $this->getID3infoItem($fi, '???', 'video', 'dataformat');
				$v_bitrate = round($this->getID3infoItem($fi, 0, 'video', 'bitrate') / 1000);
				$v_bitrate_mode = $this->getID3infoItem($fi, '???', 'video', 'bitrate_mode');
				$v_framerate = $this->getID3infoItem($fi, '???', 'video', 'frame_rate');
				$v_width = $this->getID3infoItem($fi, '???', 'video', 'resolution_x');
				$v_height = $this->getID3infoItem($fi, '???', 'video', 'resolution_y');
				$v_par = $this->getID3infoItem($fi, 1.0, 'video', 'pixel_aspect_ratio');
				$v_codec = $this->getID3infoItem($fi, '', 'video', 'codec');

				$g_bitrate = round($this->getID3infoItem($fi, 0, 'bitrate') / 1000);
				$g_playtime_str = $this->getID3infoItem($fi, '???', 'playtime_string');

				$content = '<dl>
						<dt>Audio</dt><dd>' . $a_fmt . (!empty($a_codec) ? ' (' . $a_codec . ')' : '') .
											(!empty($a_channels) ? ($a_channels === 1 ? ' (mono)' : $a_channels === 2 ? ' (stereo)' : ' (' . $a_channels . ' channels)') : '') .
											': ' . $a_samplerate . ' kHz @ ' . $a_bitrate . ' kbps (' . strtoupper($a_bitrate_mode) . ')' .
											($a_streamcount > 1 ? ' (' . $a_streamcount . ' streams)' : '') .
									'</dd>
						<dt>Video</dt><dd>' . $v_fmt . (!empty($v_codec) ? ' (' . $v_codec . ')' : '') .  ': ' . $v_framerate . ' fps @ ' . $v_bitrate . ' kbps (' . strtoupper($v_bitrate_mode) . ')' .
											($v_par != 1.0 ? ', PAR: ' . $v_par : '') .
									'</dd>
						<dt>${width}</dt><dd>' . $v_width . 'px</dd>
						<dt>${height}</dt><dd>' . $v_height . 'px</dd>
						<dt>${length}</dt><dd>' . $g_playtime_str . '</dd>
						<dt>${bitrate}</dt><dd>' . $g_bitrate . 'kbps</dd>
					</dl>';
				break;

			default:
				// fall back to 'no preview available' (if getID3 didn't deliver instead...)
				break;
			}

			if (!empty($fi))
			{
				if (empty($fi['error']))
				{
					try
					{
						self::clean_EXIF_results($fi);
						ob_start();
							var_dump($fi);
						$dump = ob_get_clean();

						$postdiag_HTML .= '<pre>' . "\n" . $dump . "\n" . '</pre>';
						//@file_put_contents('getid3.log', $dump);
					}
					catch(Exception $e)
					{
						// ignore
						$postdiag_HTML .= '<p class="err_info">' . $e->getMessage() . '</p>';
					}
				}
				else
				{
					$postdiag_HTML .= '<p class="err_info">' . implode(', ', $fi['error']) . '</p>';
				}
			}
			break;
		}

		if ($preview_HTML === null)
		{
			$preview_HTML = '${nopreview}';
		}

		if (!empty($preview_HTML))
		{
			$content .= '<h3>${preview}</h3>' . $preview_HTML;
		}
		if (!empty($postdiag_HTML))
		{
			$content .= '<h3>Diagnostics</h3>' . $postdiag_HTML;
		}

		$json['content'] = self::compressHTML('<div class="' . $content_classes . '">' . $content . '</div>');

		return $json;
	}

	/**
	 * Traverse the getID3 info[] array tree and fetch the item pointed at by the variable number of indices specified
	 * as additional parameters to this function.
	 *
	 * Return the default value when the indicated element does not exist in the info[] set; otherwise return the located item.
	 *
	 * The purpose of this method is to act as a safe go-in-between for the fileManager to collect arbitrary getID3 data and
	 * not get a PHP error when some item in there does not exist.
	 */
	public /* static */ function getID3infoItem($getid3_info_obj, $default_value /* , ... */ )
	{
		$rv = false;
		$argc = func_num_args();

		for ($i = 2; $i < $argc; $i++)
		{
			if (!is_array($getid3_info_obj))
			{
				return $default_value;
			}

			$index = func_get_arg($i);
			if (array_key_exists($index, $getid3_info_obj))
			{
				$getid3_info_obj = $getid3_info_obj[$index];
			}
			else
			{
				return $default_value;
			}
		}
		return $getid3_info_obj;
	}

	// helper function for clean_EXIF_results() as PHP < 5.3 lacks lambda functions
	protected static function __clean_EXIF_results(&$value, $key)
	{
		if (is_string($value))
		{
			//  // $dump may dump object IDs and other binary stuff, which will completely b0rk json_encode: make it palatable:
			//  //$dump = html_entity_encode($value, ENT_NOQUOTES, 'UTF-8');
			//  // strip the NULs out:
			//  $dump = str_replace('&#0;', '?', $dump);
			//  //$dump = html_entity_decode(strip_tags($dump), ENT_QUOTES, 'UTF-8');
			//  // since the regex matcher leaves NUL bytes alone, we do those above in undecoded form; the rest is treated here
			//  $dump = preg_replace("/[^ -~\n\r\t]/", '?', $dump); // remove everything outside ASCII range; some of the high byte values seem to crash json_encode()!
			//  // and reduce long sequences of unknown charcodes:
			//  $dump = preg_replace('/\?{8,}/', '???????', $dump);
			//  //$dump = html_entity_encode(strip_tags($dump), ENT_NOQUOTES, 'UTF-8');

			if (FileManagerUtility::isBinary($value))
			{
				$value = '(binary data... length = ' . strlen($value) . ')';
			}
		}
	}

	protected static function clean_EXIF_results(&$arr)
	{
		// see http://nl2.php.net/manual/en/function.array-walk-recursive.php#81835
		// --> we don't mind about it because we're not worried about the references occurring in here, now or later.
		// Indeed, that does assume we (as in 'we' being this particular function!) know about how the
		// data we process will be used. Risky, but fine with me. Hence the 'protected'.
		array_walk_recursive($arr, 'self::__clean_EXIF_results');
	}

	/**
	 * Delete a file or directory, inclusing subdirectories and files.
	 *
	 * Return TRUE on success, FALSE when an error occurred.
	 *
	 * Note that the routine will try to persevere and keep deleting other subdirectories
	 * and files, even when an error occurred for one or more of the subitems: this is
	 * a best effort policy.
	 */
	protected function unlink($legal_url, $mime_filters)
	{
		$rv = true;

		// must transform here so alias/etc. expansions inside legal_url_path2file_path() get a chance:
		$file = $this->legal_url_path2file_path($legal_url);

		if(is_dir($file))
		{
			$dir = self::enforceTrailingSlash($file);
			$url = self::enforceTrailingSlash($legal_url);
			$coll = $this->scandir($dir, '*', false, 0, ~GLOB_NOHIDDEN);
			if ($coll !== false)
			{
				foreach ($coll['dirs'] as $f)
				{
					if($f == '.' || $f == '..')
						continue;

					$rv &= $this->unlink($url . $f, $mime_filters);
				}
				foreach ($coll['files'] as $f)
				{
					$rv &= $this->unlink($url . $f, $mime_filters);
				}
			}
			else
			{
				$rv = false;
			}

			$rv &= @rmdir($file);
		}
		else if (file_exists($file))
		{
			if (is_file($file))
			{
				$mime = $this->getMimeType($file);
				if (!$this->IsAllowedMimeType($mime, $mime_filters))
					return false;
			}

			$rv2 = @unlink($file);
			if ($rv2)
				$rv &= $this->deleteThumb($legal_url);
			else
				$rv = false;
		}
		return $rv;
	}

	/**
	 * glob() wrapper: accepts the same options as Tooling.php::safe_glob()
	 *
	 * However, this method will also ensure the '..' directory entry is only returned,
	 * even while asked for, when the parent directory can be legally traversed by the FileManager.
	 *
	 * Return a dual array (possibly empty) of directories and files, or FALSE on error.
	 *
	 * IMPORTANT: this function GUARANTEES that, when present at all, the double-dot '..'
	 *            entry is the very last entry in the array.
	 *            This guarantee is important as onView() et al depend on it.
	 */
	public function scandir($dir, $filemask, $see_thumbnail_dir, $glob_flags_or, $glob_flags_and)
	{
		// list files, except the thumbnail folder itself or any file in it:
		$dir = self::enforceTrailingSlash($dir);

		$just_below_thumbnail_dir = false;
		if (!$see_thumbnail_dir)
		{
			$tnpath = $this->url_path2file_path($this->options['thumbnailPath']);
			if (FileManagerUtility::startswith($dir, $tnpath))
				return false;

			$tnparent = $this->url_path2file_path(self::getParentDir($this->options['thumbnailPath']));
			$just_below_thumbnail_dir = ($dir == $tnparent);

			$tndir = basename(substr($this->options['thumbnailPath'], 0, -1));
		}

		$at_basedir = ($this->options['assumed_base_filepath'] == $dir);

		$flags = GLOB_NODOTS | GLOB_NOHIDDEN | GLOB_NOSORT;
		$flags &= $glob_flags_and;
		$flags |= $glob_flags_or;
		$coll = safe_glob($dir . $filemask, $flags);

		FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__);

		if ($coll !== false)
		{
			if ($just_below_thumbnail_dir)
			{
				foreach($coll['dirs'] as $k => $dir)
				{
					if ($dir === $tndir)
					{
						unset($coll['dirs'][$k]);
						break;
					}
				}
			}

			if (!$at_basedir)
			{
				$coll['dirs'][] = '..';
			}

			//$coll['special_indir_mappings'] = array(array(), array());
		}

		FM_vardumper($this, __FUNCTION__ . ' @ ' . __LINE__);

		return $coll;
	}

	/**
	 * Make a cleaned-up, unique filename
	 *
	 * Return the file (dir + name + ext), or a unique, yet non-existing, variant thereof, where the filename
	 * is appended with a '_' and a number, e.g. '_1', when the file itself already exists in the given
	 * directory. The directory part of the returned value equals $dir.
	 *
	 * Return NULL when $file is empty or when the specified directory does not reside within the
	 * directory tree rooted by options['directory']
	 *
	 * Note that the given filename will be converted to a legal filename, containing a filesystem-legal
	 * subset of ASCII characters only, before being used and returned by this function.
	 *
	 * @param mixed $fileinfo     either a string containing a filename+ext or an array as produced by pathinfo().
	 * @daram string $dir         path pointing at where the given file may exist.
	 *
	 * @return a filepath consisting of $dir and the cleaned up and possibly sequenced filename and file extension
	 *         as provided by $fileinfo.
	 */
	protected function getUniqueName($fileinfo, $dir)
	{
		$dir = self::enforceTrailingSlash($dir);

		if (is_string($fileinfo))
		{
			$fileinfo = pathinfo($fileinfo);
		}

		if (!is_array($fileinfo) || !$fileinfo['filename']) return null;


		/*
		 * since 'pagetitle()' is used to produce a unique, non-existing filename, we can forego the dirscan
		 * and simply check whether the constructed filename/path exists or not and bump the suffix number
		 * by 1 until it does not, thus quickly producing a unique filename.
		 *
		 * This is faster than using a dirscan to collect a set of existing filenames and feeding them as
		 * an option array to pagetitle(), particularly for large directories.
		 */
		$filename = FileManagerUtility::pagetitle($fileinfo['filename'], null, '-_., []()~!@+' /* . '#&' */, '-_,~@+#&');
		if (!$filename)
			return null;

		// also clean up the extension: only allow alphanumerics in there!
		$ext = FileManagerUtility::pagetitle(!empty($fileinfo['extension']) ? $fileinfo['extension'] : null);
		$ext = (!empty($ext) ? '.' . $ext : null);
		// make sure the generated filename is SAFE:
		$fname = $filename . $ext;
		$file = $dir . $fname;
		if (file_exists($file))
		{
			/*
			 * make a unique name. Do this by postfixing the filename with '_X' where X is a sequential number.
			 *
			 * Note that when the input name is already such a 'sequenced' name, the sequence number is
			 * extracted from it and sequencing continues from there, hence input 'file_5' would, if it already
			 * existed, thus be bumped up to become 'file_6' and so on, until a filename is found which
			 * does not yet exist in the designated directory.
			 */
			$i = 1;
			if (preg_match('/^(.*)_([1-9][0-9]*)$/', $filename, $matches))
			{
				$i = intval($matches[2]);
				if ('P'.$i != 'P'.$matches[2] || $i > 100000)
				{
					// very large number: not a sequence number!
					$i = 1;
				}
				else
				{
					$filename = $matches[1];
				}
			}
			do
			{
				$fname = $filename . ($i ? '_' . $i : '') . $ext;
				$file = $dir . $fname;
				$i++;
			} while (file_exists($file));
		}

		// $fname is now guaranteed to NOT exist in the given directory
		return $fname;
	}

	/**
	 * Returns the URI path to the apropriate icon image for the given file / directory.
	 *
	 * NOTES:
	 *
	 * 1) any $path with an 'extension' of '.dir' is assumed to be a directory.
	 *
	 * 2) This method specifically does NOT check whether the given path exists or not: it just looks at
	 *    the filename extension passed to it, that's all.
	 *
	 * Note #2 is important as this enables this function to also serve as icon fetcher for ZIP content viewer, etc.:
	 * after all, those files do not exist physically on disk themselves!
	 */
	public function getIcon($file, $smallIcon)
	{
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		if (array_key_exists($ext, $this->icon_cache[!$smallIcon]))
		{
			return $this->icon_cache[!$smallIcon][$ext];
		}

		$largeDir = (!$smallIcon ? 'Large/' : '');
		$url_path = $this->options['assetBasePath'] . 'Images/Icons/' . $largeDir . $ext . '.png';
		$path = (is_file($this->url_path2file_path($url_path)))
			? $url_path
			: $this->options['assetBasePath'] . 'Images/Icons/' . $largeDir . 'default.png';

		$this->icon_cache[!$smallIcon][$ext] = $path;

		return $path;
	}

	/**
	 * Return the path to the thumbnail of the specified image, the thumbnail having its
	 * width and height limited to $width pixels.
	 *
	 * When the thumbnail image does not exist yet, it will created on the fly.
	 *
	 * @param string $legal_url    the LEGAL URL path to the original image. Is used solely
	 *                             to generate a suitable thumbnail filename.
	 *
	 * @param string $path         filesystem path to the original image. Is used to derive
	 *                             the thumbnail content from.
	 *
	 * @param integer $width       the maximum number of pixels for width of the
	 *                             thumbnail.
	 *
	 * @param integer $height      the maximum number of pixels for height of the
	 *                             thumbnail.
	 */
	public function getThumb($legal_url, $path, $width, $height, $onlyIfExistsInCache = false)
	{
		$thumb = $this->generateThumbName($legal_url, $width);
		$thumbPath = $this->url_path2file_path($this->options['thumbnailPath'] . $thumb);
		if (!is_file($thumbPath))
		{
			if ($onlyIfExistsInCache)
				return false;

			if (!file_exists(dirname($thumbPath)))
			{
				@mkdir(dirname($thumbPath), $this->options['chmod'], true);
			}
			$img = new Image($path);
			// generally save as lossy / lower-Q jpeg to reduce filesize, unless orig is PNG/GIF, higher quality for smaller thumbnails:
			$img->resize($width, $height)->save($thumbPath, min(98, max(MTFM_THUMBNAIL_JPEG_QUALITY, MTFM_THUMBNAIL_JPEG_QUALITY + 0.15 * (250 - min($width, $height)))), true);

			if (DEVELOPMENT)
			{
				$meta = $img->getMetaInfo();

				$meta['mem_usage'] = array(
					'memory used' => number_format(memory_get_peak_usage() / 1E6, 1) . ' MB',
					'memory estimated' => number_format(@$meta['fileinfo']['usage_guestimate'] / 1E6, 1) . ' MB',
					'memory suggested' => number_format(@$meta['fileinfo']['usage_min_advised'] / 1E6, 1) . ' MB'
				);

				FM_vardumper($this, 'getThumb', $meta);
			}

			unset($img);
		}
		return $this->options['thumbnailPath'] . $thumb;
	}

	/**
	 * Assistant function which produces the best possible icon image path for the given error/exception message.
	 */
	public function getIconForError($emsg, $original_filename, $small_icon)
	{
		if (empty($emsg))
		{
			// just go and pick the extension-related icon for this one; nothing is wrong today, it seems.
			$thumb_path = (!empty($original_filename) ? $original_filename : 'is.default-missing');
		}
		else
		{
			$thumb_path = 'is.default-error';

			if (strpos($emsg, 'img_will_not_fit') !== false)
			{
				$thumb_path = 'is.oversized_img';
			}
			else if (strpos($emsg, 'nofile') !== false)
			{
				$thumb_path = 'is.default-missing';
			}
			else if (strpos($emsg, 'unsupported_imgfmt') !== false)
			{
				// just go and pick the extension-related icon for this one; nothing seriously wrong here.
				$thumb_path = (!empty($original_filename) ? $original_filename : $thumb_path);
			}
			else if (strpos($emsg, 'image') !== false)
			{
				$thumb_path = 'badly.broken_img';
			}
		}

		$img_filepath = $this->getIcon($thumb_path, $small_icon);

		return $img_filepath;
	}

	/**
	 * Make sure the generated thumbpath is unique for each file. To prevent
	 * reduced performance for large file sets: all thumbnails derived from any files in the entire
	 * FileManager-managed directory tree, rooted by options['directory'], can become a huge collection,
	 * so we distribute them across a directory tree, which is created on demand.
	 *
	 * The thumbnails directory tree is determined by the MD5 of the full path to the image,
	 * using the first two characters of the MD5, making for a span of 256.
	 *
	 * Note: when you expect to manage a really HUGE file collection from FM, you may dial up the
	 *       $number_of_dir_levels to 2 here.
	 */
	protected function generateThumbName($legal_url, $width = 250, $number_of_dir_levels = MTFM_NUMBER_OF_DIRLEVELS_FOR_CACHE)
	{
		$fi = pathinfo($legal_url);
		$ext = strtolower(!empty($fi['extension']) ? $fi['extension'] : '');
		switch ($ext)
		{
		case 'gif':
		case 'png':
		case 'jpg':
		case 'jpeg':
			break;

		default:
			// default to PNG, as it'll handle transparancy and full color both:
			$ext = 'png';
			break;
		}

		// as the Thumbnail is generated, but NOT guaranteed from a safe filepath (FM may be visiting unsafe
		// image files when they exist in a preloaded directory tree!) we do the full safe-filename transform
		// on the name itself.
		// The MD5 is taken from the untrammeled original, though:
		$dircode = md5($legal_url);

		$rv = '';
		for ($i = 0; $i < $number_of_dir_levels; $i++)
		{
			$rv .= substr($dircode, 0, 2) . '/';
			$dircode = substr($dircode, 2);
		}

		$fn = '_' . $fi['filename'];
		$fn = substr($dircode, 0, 4) . preg_replace('/[^A-Za-z0-9]+/', '_', $fn);
		$fn = substr($fn . $dircode, 0, 38);
		$ext = preg_replace('/[^A-Za-z0-9]+/', '_', $ext);

		$rv .= $fn . '-' . $width . '.' . $ext;
		return $rv;
	}

	protected function deleteThumb($legal_url)
	{
		// generate a thumbnail name with embedded wildcard for the size parameter:
		$thumb = $this->generateThumbName($legal_url, '*');
		$tfi = pathinfo($thumb);
		$thumbnail_subdir = $tfi['dirname'];
		$thumbPath = $this->url_path2file_path($this->options['thumbnailPath'] . $thumbnail_subdir);
		$thumbPath = self::enforceTrailingSlash($thumbPath);

		// remove thumbnails (any size) and any other related cached files (TODO: future version should cache getID3 metadata as well -- and delete it here!)
		$coll = $this->scandir($thumbPath, $tfi['filename'] . '.*', true, 0, ~GLOB_NOHIDDEN);

		$rv = true;
		if ($coll !== false)
		{
			foreach($coll['files'] as $filename)
			{
				$file = $thumbPath . $filename;
				$rv &= @unlink($file);
			}
		}

		// as the thumbnail subdirectory may now be entirely empty, try to remove it as well,
		// but do NOT yack when we don't succeed: there may be other thumbnails, etc. in there still!

		while ($thumbnail_subdir > '/')
		{
			// try to NOT delete the thumbnails base directory itself; we MAY not be able to recreate it later on demand!
			$thumbPath = $this->url_path2file_path($this->options['thumbnailPath'] . $thumbnail_subdir);
			@rmdir($thumbPath);

			$thumbnail_subdir = self::getParentDir($thumbnail_subdir);
		}

		return $rv;   // when thumbnail does not exist, say it is succesfully removed: all that counts is it doesn't exist anymore when we're done here.
	}










	/**
	 * Safe replacement of dirname(); does not care whether the input has a trailing slash or not.
	 *
	 * Return FALSE when the path is attempting to get the parent of '/'
	 */
	public static function getParentDir($path)
	{
		/*
		 * on Windows, you get:
		 *
		 * dirname("/") = "\"
		 * dirname("y/") = "."
		 * dirname("/x") = "\"
		 *
		 * so we'd rather not use dirname()   :-(
		 */
		if (!is_string($path))
			return false;
		$path = rtrim($path, '/');
		// empty directory or a path with only 1 character in it cannot be a parent+child: that would be 2 at the very least when it's '/a': parent is root '/' then:
		if (strlen($path) <= 1)
			return false;

		$p2 = strrpos($path, '/' /* , -1 */ );  // -1 as extra offset is not good enough? Nope. At least not for my Win32 PHP 5.3.1. Yeah, sounds like a PHP bug to me. So we rtrim() now...
		if ($p2 === false)
		{
			return false; // tampering!
		}
		$prev = substr($path, 0, $p2 + 1);
		return $prev;
	}

	/**
	 * Return the URI absolute path to the script pointed at by the current URI request.
	 * For example, if the request was 'http://site.org/dir1/dir2/script.php', then this method will
	 * return '/dir1/dir2/script.php'.
	 *
	 * This is equivalent to $_SERVER['SCRIPT_NAME']
	 */
	public /* static */ function getRequestScriptURI()
	{
		// see also: http://php.about.com/od/learnphp/qt/_SERVER_PHP.htm
		$path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);

		return $path;
	}

	/**
	 * Return the URI absolute path to the directory pointed at by the current URI request.
	 * For example, if the request was 'http://site.org/dir1/dir2/script', then this method will
	 * return '/dir1/dir2/'.
	 *
	 * Note that the path is returned WITH a trailing slash '/'.
	 */
	public /* static */ function getRequestPath()
	{
		// see also: http://php.about.com/od/learnphp/qt/_SERVER_PHP.htm
		$path = self::getParentDir($this->getRequestScriptURI());
		$path = self::enforceTrailingSlash($path);

		return $path;
	}

	/**
	 * Normalize an absolute path by converting all slashes '/' and/or backslashes '\' and any mix thereof in the
	 * specified path to UNIX/MAC/Win compatible single forward slashes '/'.
	 *
	 * Also roll up any ./ and ../ directory elements in there.
	 *
	 * Throw an exception when the operation failed to produce a legal path.
	 */
	public /* static */ function normalize($path)
	{
		$path = preg_replace('/(\\\|\/)+/', '/', $path);

		/*
		 * fold '../' directory parts to prevent malicious paths such as 'a/../../../../../../../../../etc/'
		 * from succeeding
		 *
		 * to prevent screwups in the folding code, we FIRST clean out the './' directories, to prevent
		 * 'a/./.././.././.././.././.././.././.././.././../etc/' from succeeding:
		 */
		$path = preg_replace('#/(\./)+#', '/', $path);

		// now temporarily strip off the leading part up to the colon to prevent entries like '../d:/dir' to succeed when the site root is 'c:/', for example:
		$lead = '';
		// the leading part may NOT contain any directory separators, as it's for drive letters only.
		// So we must check in order to prevent malice like /../../../../../../../c:/dir from making it through.
		if (preg_match('#^([A-Za-z]:)?/(.*)$#', $path, $matches))
		{
			$lead = $matches[1];
			$path = '/' . $matches[2];
		}

		while (($pos = strpos($path, '/..')) !== false)
		{
			$prev = substr($path, 0, $pos);
			/*
			 * on Windows, you get:
			 *
			 * dirname("/") = "\"
			 * dirname("y/") = "."
			 * dirname("/x") = "\"
			 *
			 * so we'd rather not use dirname()   :-(
			 */
			$p2 = strrpos($prev, '/');
			if ($p2 === false)
			{
				throw new FileManagerException('path_tampering:' . $path);
			}
			$prev = substr($prev, 0, $p2);
			$next = substr($path, $pos + 3);
			if ($next && $next[0] != '/')
			{
				throw new FileManagerException('path_tampering:' . $path);
			}
			$path = $prev . $next;
		}

		$path = $lead . $path;

		/*
		 * iff there was such a '../../../etc/' attempt, we'll know because there'd be an exception thrown in the loop above.
		 */

		return $path;
	}


	/**
	 * Accept a URI relative or absolute path and transform it to an absolute URI path, i.e. rooted against DocumentRoot.
	 *
	 * Relative paths are assumed to be relative to the current request path, i.e. the getRequestPath() produced path.
	 *
	 * Note: as it uses normalize(), any illegal path will throw an FileManagerException
	 *
	 * Returns a fully normalized URI absolute path.
	 */
	public function rel2abs_url_path($path)
	{
		$path = str_replace('\\', '/', $path);
		if (!FileManagerUtility::startsWith($path, '/'))
		{
			$based = $this->getRequestPath();
			$path = $based . $path;
		}
		return $this->normalize($path);
	}

	/**
	 * Accept an absolute URI path, i.e. rooted against DocumentRoot, and transform it to a LEGAL URI absolute path, i.e. rooted against options['directory'].
	 *
	 * Relative paths are assumed to be relative to the current request path, i.e. the getRequestPath() produced path.
	 *
	 * Note: as it uses normalize(), any illegal path will throw a FileManagerException
	 *
	 * Returns a fully normalized LEGAL URI path.
	 *
	 * Throws a FileManagerException when the given path cannot be converted to a LEGAL URL, i.e. when it resides outside the options['directory'] subtree.
	 */
	public function abs2legal_url_path($path)
	{
		$root = $this->options['directory'];

		$path = $this->rel2abs_url_path($path);

		// but we MUST make sure the path is still a LEGAL URI, i.e. sitting inside options['directory']:
		if (strlen($path) < strlen($root))
			$path = self::enforceTrailingSlash($path);

		if (!FileManagerUtility::startsWith($path, $root))
		{
			throw new FileManagerException('path_tampering:' . $path);
		}

		$path = str_replace($root, '/', $path);

		return $path;
	}

	/**
	 * Accept a URI relative or absolute LEGAL URI path and transform it to an absolute URI path, i.e. rooted against DocumentRoot.
	 *
	 * Relative paths are assumed to be relative to the current request path, i.e. the getRequestPath() produced path.
	 *
	 * Note: as it uses normalize(), any illegal path will throw a FileManagerException
	 *
	 * Returns a fully normalized URI absolute path.
	 */
	public function legal2abs_url_path($path)
	{
		$root = $this->options['directory'];

		$path = str_replace('\\', '/', $path);
		if (FileManagerUtility::startsWith($path, '/'))
		{
			// clip the trailing '/' off the $root path as $path has a leading '/' already:
			$path = substr($root, 0, -1) . $path;
		}

		$path = $this->rel2abs_url_path($path);

		// but we MUST make sure the path is still a LEGAL URI, i.e. sutting inside options['directory']:
		if (strlen($path) < strlen($root))
			$path = self::enforceTrailingSlash($path);

		if (!FileManagerUtility::startsWith($path, $root))
		{
			throw new FileManagerException('path_tampering:' . $path);
		}
		return $path;
	}

	/**
	 * Accept a URI relative or absolute LEGAL URI path and transform it to an absolute LEGAL URI path, i.e. rooted against options['directory'].
	 *
	 * Relative paths are assumed to be relative to the options['directory'] directory. This makes them equivalent to absolute paths within
	 * the LEGAL URI tree and this fact may seem odd. Alas, the FM frontend sends requests without the leading slash and it's those that
	 * we wish to resolve here, after all. So, yes, this deviates from the general principle applied elesewhere in the code. :-(
	 * Nevertheless, it's easier than scanning and tweaking the FM frontend everywhere.
	 *
	 * Note: as it uses normalize(), any illegal path will throw an FileManagerException
	 *
	 * Returns a fully normalized LEGAL URI absolute path.
	 */
	public function rel2abs_legal_url_path($path)
	{
		if (0) // TODO: remove the 'relative is based on options['directory']' hack when the frontend has been fixed...
		{
			$path = $this->legal2abs_url_path($path);

			$root = $this->options['directory'];

			// clip the trailing '/' off the $root path before reduction:
			$path = str_replace(substr($root, 0, -1), '', $path);
		}
		else
		{
			$path = str_replace('\\', '/', $path);
			if (!FileManagerUtility::startsWith($path, '/'))
			{
				$path = '/' . $path;
			}

			$path = $this->normalize($path);
		}

		return $path;
	}

	/**
	 * Return the filesystem absolute path for the relative or absolute URI path.
	 *
	 * Note: as it uses normalize(), any illegal path will throw an FileManagerException
	 *
	 * Returns a fully normalized filesystem absolute path.
	 */
	public function url_path2file_path($url_path)
	{
		$url_path = $this->rel2abs_url_path($url_path);

		$path = $this->options['assumed_root_filepath'] . $url_path;
		//$path = $this->normalize($path);    -- taken care of by rel2abs_url_path already
		return $path;
	}

	/**
	 * Return the filesystem absolute path for the relative URI path or absolute LEGAL URI path.
	 *
	 * Note: as it uses normalize(), any illegal path will throw an FileManagerException
	 *
	 * Returns a fully normalized filesystem absolute path.
	 */
	public function legal_url_path2file_path($url_path)
	{
		$path = $this->rel2abs_legal_url_path($url_path);

		$path = substr($this->options['assumed_base_filepath'], 0, -1) . $path;

		return $path;
	}

	public static function enforceTrailingSlash($string)
	{
		return (strrpos($string, '/') === strlen($string) - 1 ? $string : $string . '/');
	}





	/**
	 * Produce minimized HTML output; used to cut don't on the content fed
	 * to JSON_encode() and make it more readable in raw debug view.
	 */
	public static function compressHTML($str)
	{
		// brute force: replace tabs by spaces and reduce whitespace series to a single space.
		//$str = preg_replace('/\s+/', ' ', $str);

		return $str;
	}


	protected /* static */ function modify_json4exception(&$jserr, $emsg, $mode = 0)
	{
		if (empty($emsg))
			return;

		// only set up the new json error report array when this is the first exception we got:
		if (empty($jserr['error']))
		{
			// check the error message and see if it is a translation code word (with or without parameters) or just a generic error report string
			$e = explode(':', $emsg, 2);
			if (preg_match('/[^A-Za-z0-9_-]/', $e[0]))
			{
				// generic message. ouch.
				$jserr['error'] = $emsg;
			}
			else
			{
				$jserr['error'] = $emsg = '${backend.' . $e[0] . '}' . (isset($e[1]) ? $e[1] : '');
			}
			$jserr['status'] = 0;

			if ($mode == 1)
			{
				$jserr['content'] = self::compressHTML('<div class="margin">
						${nopreview}
						<div class="failure_notice">
							<h3>${error}</h3>
							<p>mem usage: ' . number_format(memory_get_usage() / 1E6, 2) . ' MB : ' . number_format(memory_get_peak_usage() / 1E6, 2) . ' MB</p>
							<p>' . $emsg . '</p>
						</div>
					</div>');       // <br/><button value="' . $url . '">${download}</button>
			}
		}
	}






	public function getAllowedMimeTypes($mime_filter = null)
	{
		$mimeTypes = array();

		if (empty($mime_filter)) return null;
		$mset = explode(',', $mime_filter);
		for($i = count($mset) - 1; $i >= 0; $i--)
		{
			if (strpos($mset[$i], '/') === false)
				$mset[$i] .= '/';
		}

		$mimes = $this->getMimeTypeDefinitions();

		foreach ($mimes as $k => $mime)
		{
			if ($k === '.')
				continue;

			foreach($mset as $filter)
			{
				if (FileManagerUtility::startsWith($mime, $filter))
					$mimeTypes[] = $mime;
			}
		}

		return $mimeTypes;
	}

	public function getMimeTypeDefinitions()
	{
		static $mimes;

		$pref_ext = array();

		if (!$mimes)
		{
			$mimes = parse_ini_file($this->options['mimeTypesPath']);

			//FM_vardumper($this, 'getMimeTypeDefinitions', $mimes);

			if (is_array($mimes))
			{
				foreach($mimes as $k => $v)
				{
					$m = explode(',', (string)$v);
					$mimes[$k] = $m[0];
					$p = null;
					if (!empty($m[1]))
					{
						$p = trim($m[1]);
					}
					// is this the preferred extension for this mime type? Or is this the first known extension for the given mime type?
					if ($p == '*' || !array_key_exists($m[0], $pref_ext))
					{
						$pref_ext[$m[0]] = $k;
					}
				}

				// stick the mime-to-extension map into an 'illegal' index:
				$mimes['.'] = $pref_ext;
			}
			else
			{
				$mimes = false;
			}
		}

		if (!is_array($mimes)) $mimes = array(); // prevent faulty mimetype ini file from b0rking other code sections.

		return $mimes;
	}

	public function IsAllowedMimeType($mime_type, $mime_filters)
	{
		if (empty($mime_type))
			return false;
		if (!is_array($mime_filters))
			return true;

		return in_array($mime_type, $mime_filters);
	}

	/**
	 * Returns (if possible) the mimetype of the given file
	 *
	 * @param string $file
	 * @param boolean $just_guess when TRUE, files are not 'sniffed' to derive their actual mimetype
	 *                            but instead only the swift (and blunt) process of guestimating
	 *                            the mime type from the file extension is performed.
	 */
	public function getMimeType($file, $just_guess = false)
	{
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		$mime = null;
		$ini = error_reporting(0);
		if ($just_guess && function_exists('finfo_open') && $f = finfo_open(FILEINFO_MIME, getenv('MAGIC')))
		{
			$mime = finfo_file($f, $file);
			// some systems also produce the cracter encoding with the mime type; strip if off:
			$ma = explode(';', $mime);
			$mime = $ma[0];
			finfo_close($f);
		}
		error_reporting($ini);

		// UPLOAD delivers files in temporary storage with extensions NOT matching the mime type, so we don't
		// filter on extension; we just let getID3 go ahead and content-sniff the mime type.
		// Since getID3::analyze() is a quite costly operation, we like to do it only ONCE per file,
		// so we cache the last entries.
		if (empty($mime) && !$just_guess)
		{
			$fi = $this->getFileInfo($file);
			if (!empty($fi['mime_type']))
				$mime = $fi['mime_type'];
		}

		if ((empty($mime) || $mime == 'application/octet-stream') && strlen($ext) > 0)
		{
			$ext2mimetype_arr = $this->getMimeTypeDefinitions();

			if (array_key_exists($ext, $ext2mimetype_arr))
				$mime = $ext2mimetype_arr[$ext];
		}

		if (empty($mime))
			$mime = 'application/octet-stream';

		return $mime;
	}

	/**
	 * Return the first known extension for the given mime type.
	 *
	 * Return NULL when no known extension is found.
	 */
	public function getExtFromMime($mime)
	{
		$ext2mimetype_arr = $this->getMimeTypeDefinitions();
		$mime2ext_arr = $ext2mimetype_arr['.'];

		if (array_key_exists($mime, $mime2ext_arr))
			return $mime2ext_arr[$mime];

		return null;
	}

	/**
	 * Returns (if possible) all info about the given file, mimetype, dimensions, the works
	 *
	 * @param string $file    physical filesystem path to the file we want to know all about
	 *
	 * @return the info array as produced by getID3::analyze() + getID3::CopyTagsToComments()
	 */
	public function getFileInfo($file)
	{
		$hash = md5($file . ':' . @filemtime($file));

		$age_limit = $this->getid3_cache_lru_ts - MTFM_MIN_GETID3_CACHESIZE;

		// when hash exists in cache, return that one:
		if (array_key_exists($hash, $this->getid3_cache))
		{
			$rv = $this->getid3_cache[$hash];

			// mark as LRU entry; only update the timestamp when it's rather old (age/2) to prevent
			// cache flushing due to hammering of a few entries:
			if ($this->getid3_cache[$hash]['cache_timestamp'] < $age_limit + MTFM_MIN_GETID3_CACHESIZE / 2)
			{
				$this->getid3_cache[$hash]['cache_timestamp'] = $this->getid3_cache_lru_ts++;
			}
		}
		else
		{
			$this->getid3->analyze($file);
			getid3_lib::CopyTagsToComments($this->getid3->info);

			$rv = $this->getid3->info;

			// store it in the cache; mark as LRU entry
			$rv['cache_timestamp'] = $this->getid3_cache_lru_ts++;
			$this->getid3_cache[$hash] = $rv;

			/*
			Cleanup/cache size restriction algorithm:

			Randomly probe the cache and check whether the probe has a 'timestamp' older than the configured
			minimum required lifetime. When the probe is older, it is discarded from the cache.

			As the probe is assumed to be perfectly random, further assuming we've got a cache size of N,
			then the chance we pick a probe older then age A is (N - A) / N  -- picking any age X has a
			chance of 1/N as random implies flat distribution. Hitting any of the most recent A entries
			is A * 1/N, hence picking any older item is 1 - A/N == (N - A) / N

			This means the growth of the cache beyond the given age limit A is a logarithmic curve, but
			we like to have a guaranteed upper limit significantly below N = +Inf, so we probe the cache
			TWICE for each addition: given a cache size of 2N, one of these probes should, on average,
			be successful, thus removing one cache entry on average for a cache size of 2N. As we only
			add 1 item at the same time, the statistically expected bound of the cache will be 2N.
			As chances increase for both probes to be successful when cache size increases, the risk
			of a (very) large cache size at any point in time is dwindingly small, while cost is constant
			per cache transaction (insert + dual probe).

			This scheme is expected to be faster (thanks to log growth curve and linear insert/prune costs)
			than the usual where one keeps meticulous track of the entries and their age and entries are
			discarded in order, oldest first.
			*/
			$probe_index = array_rand($this->getid3_cache);
			$probe = &$this->getid3_cache[$probe_index];
			if ($probe['cache_timestamp'] < $age_limit)
			{
				// discard antiquated entry:
				unset($this->getid3_cache[$probe_index]);
			}
			$probe_index = array_rand($this->getid3_cache);
			$probe = &$this->getid3_cache[$probe_index];
			if ($probe['cache_timestamp'] < $age_limit)
			{
				// discard antiquated entry:
				unset($this->getid3_cache[$probe_index]);
			}
		}

		return $rv;
	}





	protected /* static */ function getGETparam($name, $default_value = null)
	{
		if (is_array($_GET) && !empty($_GET[$name]))
		{
			$rv = $_GET[$name];

			// see if there's any stuff in there which we don't like
			if (!preg_match('/[^A-Za-z0-9\/~!@#$%^&*()_+{}[]\'",.?]/', $rv))
			{
				return $rv;
			}
		}
		return $default_value;
	}

	protected /* static */ function getPOSTparam($name, $default_value = null)
	{
		if (is_array($_POST) && !empty($_POST[$name]))
		{
			$rv = $_POST[$name];

			// see if there's any stuff in there which we don't like
			if (!preg_match('/[^A-Za-z0-9\/~!@#$%^&*()_+{}[]\'",.?]/', $rv))
			{
				return $rv;
			}
		}
		return $default_value;
	}
}






class FileManagerException extends Exception {}





/* Stripped-down version of some Styx PHP Framework-Functionality bundled with this FileBrowser. Styx is located at: http://styx.og5.net */
class FileManagerUtility
{
	public static function endsWith($string, $look)
	{
		return strrpos($string, $look) === strlen($string) - strlen($look);
	}

	public static function startsWith($string, $look)
	{
		return strpos($string, $look) === 0;
	}


	/**
	 * Cleanup and check against 'already known names' in optional $options array.
	 * Return a uniquified name equal to or derived from the original ($data).
	 *
	 * First clean up the given name ($data): by default all characters not part of the
	 * set [A-Za-z0-9_] are converted to an underscore '_'; series of these underscores
	 * are reduced to a single one, and characters in the set [_.,&+ ] are stripped from
	 * the lead and tail of the given name, e.g. '__name' would therefor be reduced to
	 * 'name'.
	 *
	 * Next, check the now cleaned-up name $data against an optional set of names ($options array)
	 * and return the name itself when it does not exist in the set,
	 * otherwise return an augmented name such that it does not exist in the set
	 * while having been constructed as name plus '_' plus an integer number,
	 * starting at 1.
	 *
	 * Example:
	 * If the set is {'file', 'file_1', 'file_3'} then $data = 'file' will return
	 * the string 'file_2' instead, while $data = 'fileX' will return that same
	 * value: 'fileX'.
	 *
	 * @param string $data     the name to be cleaned and checked/uniquified
	 * @param array $options   an optional array of strings to check the given name $data against
	 * @param string $extra_allowed_chars     optional set of additional characters which should pass
	 *                                        unaltered through the cleanup stage. a dash '-' can be
	 *                                        used to denote a character range, while the literal
	 *                                        dash '-' itself, when included, should be positioned
	 *                                        at the very start or end of the string.
	 *
	 *                                        Note that ] must NOT need to be escaped; we do this
	 *                                        ourselves.
	 * @param string $trim_chars              optional set of additional characters which are trimmed off the
	 *                                        start and end of the name ($data); note that de dash
	 *                                        '-' is always treated as a literal dash here; no
	 *                                        range feature!
	 *                                        The basic set of characters trimmed off the name is
	 *                                        [. ]; this set cannot be reduced, only extended.
	 *
	 * @return cleaned-up and uniquified name derived from ($data).
	 */
	public static function pagetitle($data, $options = null, $extra_allowed_chars = null, $trim_chars = null)
	{
		static $regex;
		if (!$regex){
			$regex = array(
				explode(' ', 'Æ æ Œ œ ß Ü ü Ö ö Ä ä À Á Â Ã Ä Å &#260; &#258; Ç &#262; &#268; &#270; &#272; Ð È É Ê Ë &#280; &#282; &#286; Ì Í Î Ï &#304; &#321; &#317; &#313; Ñ &#323; &#327; Ò Ó Ô Õ Ö Ø &#336; &#340; &#344; Š &#346; &#350; &#356; &#354; Ù Ú Û Ü &#366; &#368; Ý Ž &#377; &#379; à á â ã ä å &#261; &#259; ç &#263; &#269; &#271; &#273; è é ê ë &#281; &#283; &#287; ì í î ï &#305; &#322; &#318; &#314; ñ &#324; &#328; ð ò ó ô õ ö ø &#337; &#341; &#345; &#347; š &#351; &#357; &#355; ù ú û ü &#367; &#369; ý ÿ ž &#378; &#380;'),
				explode(' ', 'Ae ae Oe oe ss Ue ue Oe oe Ae ae A A A A A A A A C C C D D D E E E E E E G I I I I I L L L N N N O O O O O O O R R S S S T T U U U U U U Y Z Z Z a a a a a a a a c c c d d e e e e e e g i i i i i l l l n n n o o o o o o o o r r s s s t t u u u u u u y y z z z'),
			);
		}

		if (empty($data))
				return $data;

		// fixup $extra_allowed_chars to ensure it's suitable as a character sequence for a set in a regex:
		//
		// Note:
		//   caller must ensure a dash '-', when to be treated as a separate character, is at the very end of the string
		if (is_string($extra_allowed_chars))
		{
			$extra_allowed_chars = str_replace(']', '\]', $extra_allowed_chars);
			if (strpos($extra_allowed_chars, '-') === 0)
			{
				$extra_allowed_chars = substr($extra_allowed_chars, 1) . (strpos($extra_allowed_chars, '-') != strlen($extra_allowed_chars) - 1 ? '-' : '');
			}
		}
		else
		{
			$extra_allowed_chars = '';
		}
		// accepts dots and several other characters, but do NOT tolerate dots or underscores at the start or end, i.e. no 'hidden file names' accepted, for example!
		$data = preg_replace('/[^A-Za-z0-9' . $extra_allowed_chars . ']+/', '_', str_replace($regex[0], $regex[1], $data));
		$data = trim($data, '_. ' . $trim_chars);

		//$data = trim(substr(preg_replace('/(?:[^A-z0-9]|_|\^)+/i', '_', str_replace($regex[0], $regex[1], $data)), 0, 64), '_');
		return !empty($options) ? $this->checkTitle($data, $options) : $data;
	}

	protected /* static */ function checkTitle($data, $options = array(), $i = 0)
	{
		if (!is_array($options)) return $data;

		$lwr_data = strtolower($data);

		foreach ($options as $content)
			if ($content && strtolower($content) == $lwr_data . ($i ? '_' . $i : ''))
				return $this->checkTitle($data, $options, ++$i);

		return $data.($i ? '_' . $i : '');
	}

	public static function isBinary($str)
	{
		for($i = 0; $i < strlen($str); $i++)
		{
			$c = ord($str[$i]);
			// do not accept ANY codes below SPACE, except TAB, CR and LF.
			if ($c == 255 || ($c < 32 /* SPACE */ && $c != 9 && $c != 10 && $c != 13)) return true;
		}

		return false;
	}

	/**
	 * Apply rawurlencode() to each of the elements of the given path
	 *
	 * @note
	 *   this method is provided as rawurlencode() itself also encodes the '/' separators in a path/string
	 *   and we do NOT want to 'revert' such change with the risk of also picking up other %2F bits in
	 *   the string (this assumes crafted paths can be fed to us).
	 */
	public static function rawurlencode_path($path)
	{
		return str_replace('%2F', '/', rawurlencode($path));
	}

	/**
	 * Convert a number (representing number of bytes) to a formatted string representing GB .. bytes,
	 * depending on the size of the value.
	 */
	public static function fmt_bytecount($val, $precision = 1)
	{
		$unit = array('TB', 'GB', 'MB', 'KB', 'bytes');
		for ($x = count($unit) - 1; $val >= 1024 && $x > 0; $x--)
		{
			$val /= 1024.0;
		}
		$val = round($val, ($x > 0 ? $precision : 0));
		return $val . '&#160;' . $unit[$x];
	}
}

