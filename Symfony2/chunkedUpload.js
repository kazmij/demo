
/**
 * Object can chunk upload file to server
 * @constructor
 * @param {object} config
 * config = {
 *   inputField: {object} inputField,
 *   uploadUrl: {string} uploadUrl,
 *   chunkSize: {string} chunkSize (e.g. 100KB, 1MB),
 *   maxFileSize: {string} maxFileSize (e.g. 100KB, 1MB),
 *   resumeCount: {integer} resumeCount,
 *   startHandler: {function} startHandler,
 *   listHandler: {function} listHandler,
 *   successHandler: {function) successHandler,
 *   failureHandler: {function} failureHnadler,
 *   stepHandler: {function} stepHandler,
 *   resumeHandler: {function} resumeHandler
 *   errorHandler: {function} errorHandler 
 * }
 * @returns {void}
 */
function ChunkedUpload(config) {
    /**
     * Object with logger method if console exist
     * @type SubLogger
     */
    var Logger = (function () {
        var SubLogger = {
            __noSuchMethod__: function (name, params) {
                if (typeof console === 'object') {
                    console.log('Method: ' + name + ' no exist!!!');
                }
                return null;
            }
        };
        var log = false;
        var colorFn = function (color, defaultColor) {
            if (typeof color === 'undefined') {
                color = defaultColor ? defaultColor : 'green';
            }
            return 'color: ' + color;
        };
        if (typeof console === 'object') {
            SubLogger.info = function (msg, color) {
                if (log) {
                    color = colorFn(color, 'green');
                    console.log('%c' + msg, color);
                }
            };
            SubLogger.wrn = function (msg, color) {
                if (log) {
                    color = colorFn(color, 'orange');
                    console.log('%c' + msg, color);
                }
            };
            SubLogger.err = function (msg) {
                if (log) {
                    throw new Error(msg);
                }
            };
        }
        return SubLogger;
    })();
    var convertToBytes = function (key) {
        if (/^[1-9]+[0-9]*(MB|KB)$/.test(config[key])) {
            if (/^[1-9]+[0-9]*MB$/.test(config[key])) {
                config[key] = parseInt(config[key].replace("MB", '')) * 1048576;
            } else {
                config[key] = parseInt(config[key].replace("KB", '')) * 1024;
            }
        } else {
            Logger.err('You must define valid ' + key + ' variable in config e.g. 50MB, 10MB, 100KB, 570KB!!!!');
        }
    };
    var convertToView = function (key) {
        return (Math.round(key / 1048576 * 100) / 100) + 'MB';
    };
    // 1. Validate object start config
    (function () {
        if (typeof config === 'object') {
            if (typeof config.inputField === 'object') {
                if (!document.contains(config.inputField)) {
                    Logger.err('Variable inputField is no valid html element from ChunkedUpload config!!!');
                }
            } else {
                Logger.err('Variable inputField is no valid html element from ChunkedUpload config!!!');
            }

            if (!config.uploadUrl.match(/^(https?:\/\/)?((([a-zr\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?$/i)) {
                Logger.err('Variable uploadUrl is no valid uploadUrl from ChunkedUpload config!!!');
            }

            if (typeof config.chunkSize !== 'undefined') {
                convertToBytes('chunkSize');
            } else {
                Logger.err('Variable chunkSize is no valid chunkSize (in MB,KB) from ChunkedUpload config!!!');
            }

            if (typeof config.maxFileSize !== 'undefined') {
                convertToBytes('maxFileSize');
            } else {
                Logger.err('Variable maxFileSize is no valid maxFileSize (in MB,KB) from ChunkedUpload config!!!');
            }

            if (typeof config.resumeCount !== 'integer') {
                config.resumeCount = 3;
            }
            if (typeof config.startHandler !== 'undefined') {
                if (typeof config.startHandler !== 'function') {
                    Logger.err('Variable startHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }

            if (typeof config.listHandler !== 'undefined') {
                if (typeof config.listHandler !== 'function') {
                    Logger.err('Variable listHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }

            if (typeof config.successHandler !== 'undefined') {
                if (typeof config.successHandler !== 'function') {
                    Logger.err('Variable successHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }

            if (typeof config.failureHandler !== 'undefined') {
                if (typeof config.failureHandler !== 'function') {
                    Logger.err('Variable failureHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }

            if (typeof config.stepHandler !== 'undefined') {
                if (typeof config.stepHandler !== 'function') {
                    Logger.err('Variable stepHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }
            if (typeof config.resumeHandler !== 'undefined') {
                if (typeof config.resumeHandler !== 'function') {
                    Logger.err('Variable resumeHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }
            if (typeof config.errorHandler !== 'undefined') {
                if (typeof config.errorHandler !== 'function') {
                    Logger.err('Variable errorHandler is no valid function handler from ChunkedUpload config!!! It must be empty or function!');
                }
            }

        } else {

            Logger.err("Variable config is not valid ChunkedUpload argument. Must be object: \n\
        config = {\n\
           inputField: {object} inputField (required),\n\
           uploadUrl: {string} uploadUrl (required),\n\
           chunkSize: {string} chunkSize (required),\n\
           maxFileSize: {string} maxFileSize (required),\n\
           resumeCount: {integer} resumeCount(optional),\n\
           startHandler: {function} startHandler,\n\
           listHandler: {function} listHandler (optional),\n\
           successHandler: {function) successHandler (optional),\n\
           failureHandler: {function} failureHnadler (optional)\n\
           stepHandler: {function} stepHandler (optional)\n\
           resumeHandler: {function} resumeHandler (optional)\n\
           errorHandler: {function} resumeHandler (optional)\n\
         }");
        }
    })();
    // 2. Create upload master object with all methods
    var ChunkedUploadMaster = {
        /**
         * Start upload function
         * @returns {undefined}
         */
        start: function () {
            Logger.info('Upload ' + this.files.length + ' files is started');
            this.upload();
        },
        /**
         * Upload an blobs
         * @returns {undefined}
         */
        upload: function (fileID, blobID) {
            // 1. if no arguments start upload from begin file and file blob
            if (arguments.length === 0) {
                if (this.files.length > 0) {
                    this.upload(0, 0);
                } else {
                    Logger.err('Empty files list to upload');
                }
            } else {
                //console.log(this.files[fileID]); return;
                if (typeof this.files[fileID] !== 'undefined') {
                    if (this.files[fileID].blobs[blobID] !== 'undefined') {
                        var xhr = new XMLHttpRequest();
                        // set request header if headers object exist and is no empty
                        if (typeof this.config.headers === 'object') {
                            var objKeys = Object.keys(this.config.headers);
                            if (objKeys.length > 0) {
                                for (var i = 0; i < objKeys.length; i++) {
                                    xhr.setRequestHeader(objKeys[i], this.config.headers[objKeys[i]]);
                                }
                            }
                        }
                        //open connection
                        xhr.open('PUT', this.config.uploadUrl, true);
                        xhr.setRequestHeader('X-File-Name', this.files[fileID].name);
                        xhr.setRequestHeader('X-File-Size', this.files[fileID].size);
                        xhr.setRequestHeader('X-File-Type', this.files[fileID].type);
                        xhr.setRequestHeader('X-File-ID', fileID);
                        xhr.setRequestHeader('X-File-Blob-ID', blobID);
                        xhr.setRequestHeader('X-File-BlobsCount', this.files[fileID].blobs.length);
                        xhr.send(this.files[fileID].blobs[blobID]);
                        xhr.onload = function (e) {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                var result;
                                try {
                                    result = JSON.parse(xhr.responseText);
                                } catch (e) {
                                    result = null;
                                }
                                Logger.info('Success upload file: ' + ChunkedUploadMaster.files[fileID].name + ' as file: ' + (fileID + 1) + '/' + ChunkedUploadMaster.files.length + ', blob this file: ' + (blobID + 1) + '/' + ChunkedUploadMaster.files[fileID].blobs.length);
                                ChunkedUploadMaster.files[fileID].uploadedBytes += ChunkedUploadMaster.files[fileID].blobs[blobID].size;
                                if (typeof config.stepHandler === 'function') {
                                    var stepJson = {};
                                    stepJson.uploadedPercent = Math.round(ChunkedUploadMaster.files[fileID].uploadedBytes / ChunkedUploadMaster.files[fileID].size * 100);
                                    stepJson.uploadedBytes = ChunkedUploadMaster.files[fileID].uploadedBytes;
                                    stepJson.totalBytes = ChunkedUploadMaster.files[fileID].size;
                                    stepJson.id = fileID;
                                    config.stepHandler(stepJson);
                                }
                                if (ChunkedUploadMaster.files[fileID].blobs.length === blobID + 1) {
                                    if (ChunkedUploadMaster.files.length === fileID + 1) {
                                        return ChunkedUploadMaster.finalizeUpload(result); // toDO ?
                                    } else {
                                        ChunkedUploadMaster.upload(fileID + 1, 0);
                                    }
                                } else {
                                    ChunkedUploadMaster.upload(fileID, blobID + 1);
                                }
                            } else { //resume use ?
                                Logger.err('Upload failure with code: ' + xhr.status + ' it is unknown error...');
                            }
                        };
                        xhr.onerror = function (e) {
                            if (ChunkedUploadMaster.config.resumedTmp <= ChunkedUploadMaster.config.resumeCount) {
                                ChunkedUploadMaster.config.resumedTmp++;
                                Logger.wrn('Upload failure with code: ' + xhr.status + ' it is unknown error...try resume upload this file');
                                Logger.info('Resume upload file: ' + ChunkedUploadMaster.files[fileID].name);
                                if (typeof config.resumeHandler === 'function') {
                                    config.resumeHandler(fileID);
                                }
                                ChunkedUploadMaster.upload(fileID, blobID);
                            } else {
                                if (typeof config.failureHandler === 'function') {
                                    config.failureHandler(fileID);
                                }
                                Logger.err('Upload failure with code: ' + xhr.status + ' it is unknown error...resume is not working with this');
                            }
                        };
                        Logger.info('Start upload file: ' + this.files[fileID].name + ' as file: ' + (fileID + 1) + '/' + this.files.length + ', blob this file: ' + (blobID + 1) + '/' + this.files[fileID].blobs.length);
                    } else {
                        Logger.err('An error occured.. File with id: ' + fileID + ' hasn\'t got blobs with id: ' + blobID);
                    }
                } else {
                    Logger.err('An error occured.. File with id: ' + fileID + ' is no exist!');
                }
            }

        },
        /**
         * Blobs partition to upload and get progress 
         * @returns {undefined}
         */
        setBlobs: function () {
            var tmp = [], blobs = [], fileChunk, tmpJson = [];
            for (var i = 0; i < this.files.length; i++) {
                if (this.validateFile(this.files[i])) {
                    var startSize = 0;
                    var endSize = this.files[i].size > this.config.chunkSize ? this.config.chunkSize : this.files[i].size;
                    while (startSize < this.files[i].size) {
                        fileChunk = this.files[i].slice(startSize, endSize);
                        blobs.push(fileChunk);
                        startSize = endSize;
                        if (startSize + this.config.chunkSize < this.files[i].size) {
                            endSize = startSize + this.config.chunkSize;
                        } else {
                            endSize = this.files[i].size;
                        }
                    }
                    tmpJson[i] = {
                        name: ChunkedUploadMaster.files[i].name,
                        type: ChunkedUploadMaster.files[i].type,
                        id: i,
                        sizeKB: ChunkedUploadMaster.getSize(ChunkedUploadMaster.files[i].size, 'KB'),
                        sizeMB: ChunkedUploadMaster.getSize(ChunkedUploadMaster.files[i].size, 'MB')
                    };
                    this.files[i].blobs = blobs;
                    this.files[i].uploaded = false;
                    this.files[i].uploadedBytes = 0;
                    tmp.push(this.files[i]);
                }
            }
            this.files = tmp;
            this.blobs = blobs;
            if (this.files.length) {
                if (typeof config.listHandler === 'function') {
                    config.listHandler(tmpJson);
                }
                this.start();
            } else {
                Logger.wrn('Nothing to upload ');
            }
        },
        /**
         * Function to finalize files upload
         * @returns {undefined}
         */
        finalizeUpload: function (result) {
            Logger.info('All files was successufly uploaded!');
            if (typeof config.successHandler === 'function') {
                config.successHandler(result ? result.path : null);
            }
            return true;
        },
        /**
         * Errors array from upload
         */
        errors: [],
        /**
         * Files to upload/uploaded with blobs partitions
         */
        files: [],
        /**
         * Validate file function
         * @param {type} file
         * @returns {Boolean}
         */
        validateFile: function (file) {
            if (file.size > this.config.maxFileSize) {
                this.errors.push('File "' + file.name + '" is to big to upload (' + convertToView(file.size) + ' - max is ' + convertToView(this.config.maxFileSize) + ')');
                if (typeof this.config.errorHandler === 'function') {
                    this.config.errorHandler(this.errors);
                }
                return false;
            } else {
                return true;
            }
        },
        /**
         * 
         * @param {type} size
         * @param {type} type
         * @returns {undefined}
         */
        getSize: function (size, type) {
            var str, targetSize;
            switch (type) {
                case 'MB':
                    targetSize = Math.round(size / 1048576 * 100) / 100;
                    str = targetSize + 'MB';
                    break;
                case'KB':
                    targetSize = Math.round(size / 1024 * 100) / 100;
                    str = targetSize + 'KB';
                    break;
                default:
                    str = 'undefined type';
                    break;
            }
            return str;
        }
    };
    // 3. Set upload master config
    ChunkedUploadMaster.config = config;
    ChunkedUploadMaster.config.resumedTmp = 0; //resumed count variable, used in resume after error
    // 4. Add change listener for input file field
    //config.inputField.
    document.querySelector('body').addEventListener('change', function (e) {
        e.preventDefault();
        if (e.target.getAttribute('id') === config.inputField.getAttribute('id')) {
            ChunkedUploadMaster.errors = new Array();
            ChunkedUploadMaster.files = new Array(); //reset files table
            ChunkedUploadMaster.files = ChunkedUploadMaster.files.concat(e.target.files[0]); //only one file
            if (ChunkedUploadMaster.files.length > 0) {
                //this line reset input file after change 
                config.inputField.setAttribute('type', 'input');
                config.inputField.setAttribute('type', 'file');
                if (typeof ChunkedUploadMaster.config.startHandler === 'function') {
                    ChunkedUploadMaster.config.startHandler();
                }
                ChunkedUploadMaster.setBlobs();
            } else {
                Logger.wrn('Empty files list to upload after input file filed change...');
            }
        }
    });
    //5. The End - return empty object
    return {};
}