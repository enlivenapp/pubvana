<div class="col-xs-12">
    

        <script src="//cdnjs.cloudflare.com/ajax/libs/require.js/2.3.2/require.min.js"></script>
        <script>
            define('elFinderConfig', {
                // elFinder options (REQUIRED)
                // Documentation for client options:
                // https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
                defaultOpts : {
                    url : '<?php echo $connector ?>' // connector URL (REQUIRED)
                    ,commandsOptions : {
                        edit : {
                            extraOptions : {
                                // set API key to enable Creative Cloud image editor
                                // see https://console.adobe.io/
                                creativeCloudApiKey : '',
                                // browsing manager URL for CKEditor, TinyMCE
                                // uses self location with the empty value
                                managerUrl : ''
                            }
                        }
                        ,quicklook : {
                            // to enable preview with Google Docs Viewer
                            googleDocsMimes : ['application/pdf', 'image/tiff', 'application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                        }
                    }
                    // bootCalback calls at before elFinder boot up 
                    ,bootCallback : function(fm, extraObj) {
                        /* any bind functions etc. */
                        fm.bind('init', function() {
                            // any your code
                        });
                        // for example set document.title dynamically.
                        var title = document.title;
                        fm.bind('open', function() {
                            var path = '',
                                cwd  = fm.cwd();
                            if (cwd) {
                                path = fm.path(cwd.hash) || null;
                            }
                            document.title = path? path + ':' + title : title;
                        }).bind('destroy', function() {
                            document.title = title;
                        });
                    }
                },
                managers : {
                    // 'DOM Element ID': { /* elFinder options of this DOM Element */ }
                    'elfinder': {}
                }
            });
            define('returnVoid', void 0);
            (function(){
                var // elFinder version
                    elver = '<?php echo elFinder::getApiFullVersion()?>',
                    // jQuery and jQueryUI version
                    jqver = '3.2.1',
                    uiver = '1.12.1',
                    
                    // Detect language (optional)
                    lang = (function() {
                        var locq = window.location.search,
                            fullLang, locm, lang;
                        if (locq && (locm = locq.match(/lang=([a-zA-Z_-]+)/))) {
                            // detection by url query (?lang=xx)
                            fullLang = locm[1];
                        } else {
                            // detection by browser language
                            fullLang = (navigator.browserLanguage || navigator.language || navigator.userLanguage);
                        }
                        lang = fullLang.substr(0,2);
                        if (lang === 'ja') lang = 'jp';
                        else if (lang === 'pt') lang = 'pt_BR';
                        else if (lang === 'ug') lang = 'ug_CN';
                        else if (lang === 'zh') lang = (fullLang.substr(0,5).toLowerCase() === 'zh-tw')? 'zh_TW' : 'zh_CN';
                        return lang;
                    })(),
                    
                    // Start elFinder (REQUIRED)
                    start = function(elFinder, editors, config) {
                        // load jQueryUI CSS
                        elFinder.prototype.loadCss('//cdnjs.cloudflare.com/ajax/libs/jqueryui/'+uiver+'/themes/smoothness/jquery-ui.css');
                        
                        $(function() {
                            var optEditors = {
                                    commandsOptions: {
                                        edit: {
                                            editors: Array.isArray(editors)? editors : []
                                        }
                                    }
                                },
                                opts = {};
                            
                            // Interpretation of "elFinderConfig"
                            if (config && config.managers) {
                                $.each(config.managers, function(id, mOpts) {
                                    opts = Object.assign(opts, config.defaultOpts || {});
                                    // editors marges to opts.commandOptions.edit
                                    try {
                                        mOpts.commandsOptions.edit.editors = mOpts.commandsOptions.edit.editors.concat(editors || []);
                                    } catch(e) {
                                        Object.assign(mOpts, optEditors);
                                    }
                                    // Make elFinder
                                    $('#' + id).elfinder(
                                        // 1st Arg - options
                                        $.extend(true, { lang: lang }, opts, mOpts || {}),
                                        // 2nd Arg - before boot up function
                                        function(fm, extraObj) {
                                            // `init` event callback function
                                            fm.bind('init', function() {
                                                // Optional for Japanese decoder "extras/encoding-japanese.min"
                                                delete fm.options.rawStringDecoder;
                                                if (fm.lang === 'jp') {
                                                    require(
                                                        [ 'encoding-japanese' ],
                                                        function(Encoding) {
                                                            if (Encoding.convert) {
                                                                fm.options.rawStringDecoder = function(s) {
                                                                    return Encoding.convert(s,{to:'UNICODE',type:'string'});
                                                                };
                                                            }
                                                        }
                                                    );
                                                }
                                            });
                                        }
                                    );
                                });
                            } else {
                                alert('"elFinderConfig" object is wrong.');
                            }
                        });
                    },
                    
                    // JavaScript loader (REQUIRED)
                    load = function() {
                        require(
                            [
                                'elfinder'
                                , 'extras/editors.default'       // load text, image editors
                                , 'elFinderConfig'
                            //  , 'extras/quicklook.googledocs'  // optional preview for GoogleApps contents on the GoogleDrive volume
                            ],
                            start,
                            function(error) {
                                alert(error.message);
                            }
                        );
                    },
                    
                    // is IE8? for determine the jQuery version to use (optional)
                    ie8 = (typeof window.addEventListener === 'undefined' && typeof document.getElementsByClassName === 'undefined');

                // config of RequireJS (REQUIRED)
                require.config({
                    baseUrl : '//cdnjs.cloudflare.com/ajax/libs/elfinder/'+elver+'/js',
                    paths : {
                        'jquery'   : '//cdnjs.cloudflare.com/ajax/libs/jquery/'+(ie8? '1.12.4' : jqver)+'/jquery.min',
                        'jquery-ui': '//cdnjs.cloudflare.com/ajax/libs/jqueryui/'+uiver+'/jquery-ui.min',
                        'elfinder' : 'elfinder.min',
                        'encoding-japanese': '//cdn.rawgit.com/polygonplanet/encoding.js/master/encoding.min'
                    },
                    waitSeconds : 10 // optional
                });

                // load JavaScripts (REQUIRED)
                load();

            })();
        </script>
        <!-- Element where elFinder will be created (REQUIRED) -->
        <div id="elfinder"></div>
</div>
