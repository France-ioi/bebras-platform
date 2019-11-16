window.onbeforeunload = function() {
    return i18n.t("error_reloading_iframe")
}

window.onerror = window.parent.onerror;

var t = function(item) {return item;}; function setTranslate(translateFun) { t = translateFun; }
//this.iframe.contentWindow.setTranslate(i18n.t);

// Inject ImagesLoader
var ImagesLoader = {
    newUrlImages: {},
    loadingImages: new Array(),
    imagesToPreload: null,
    contestFolder: null,
    nbImagesToLoad: 0,
    nbImagesLoaded: 0,
    nbPreloadErrors: 0,
    switchToNonStatic: false,
    preloadCallback: null,
    preloadAllImages: null,
    /* Defines what function to call once the preload phase is over */
    setCallback: function (callback) {
        this.preloadCallback = callback;
    },
/* Called by the generated contest .js file with the list of images to preload */
    setImagesToPreload: function (imagesToPreload) {
        this.imagesToPreload = imagesToPreload;
    },
    addImagesToPreload: function (imagesToPreload) {
    this.imagesToPreload = this.imagesToPreload.concat(imagesToPreload);
    },
    errorHandler: function () {
        var that = ImagesLoader;
        that.loadingImages[that.nbImagesLoaded].onload = null;
        that.loadingImages[that.nbImagesLoaded].onerror = null;
        that.nbPreloadErrors++;
        if (that.nbPreloadErrors == 4){
        alert(t("error_connexion_server"));
        }
        if (that.nbPreloadErrors == 20) {
        alert(t("error_connexion_server_bis"));
        that.nbImagesLoaded = that.nbImagesToLoad;
        }
        setTimeout(that.loadNextImage, 2000);
    },

    /* * Called after each successful load of an image. Update the interface and starts * loading the next image. */
    loadHandler: function () {
        var that = ImagesLoader;
        that.loadingImages[that.nbImagesLoaded].onload = null;
        that.loadingImages[that.nbImagesLoaded].onerror = null;
        that.nbImagesLoaded++;
        that.nbPreloadErrors = 0;
        parent.setNbImagesLoaded("" + that.nbImagesLoaded + "/" + that.nbImagesToLoad);
        setTimeout(function() { that.loadNextImage(); }, 1);
    },

    loadNextImage: function () {
        var that = ImagesLoader;
        if (that.nbImagesLoaded === that.nbImagesToLoad) {
            that.preloadCallback();
            return;
        }
        if (that.loadingImages[that.nbImagesLoaded] == undefined) {
            that.loadingImages[that.nbImagesLoaded] = new Image();
            that.loadingImages[that.nbImagesLoaded].onerror = that.errorHandler;
            that.loadingImages[that.nbImagesLoaded].onload = that.loadHandler;
            var srcImage = that.imagesToPreload[that.nbImagesLoaded];
            if (srcImage == "") {
                that.loadHandler();
                return;
            }
            if (that.nbPreloadErrors > 0) {
                var oldSrcImage = srcImage;
                srcImage += "?v=" + that.nbPreloadErrors + "_" + Parameters.teamID;
                that.newUrlImages[oldSrcImage] = srcImage;
                if (that.nbPreloadErrors > 2) {
                    that.switchToNonStatic = true;
                }
            }
            for(var i=0; i<window.config.imagesURLReplacements.length; i++) {
                srcImage = srcImage.replace(window.config.imagesURLReplacements[i][0], window.config.imagesURLReplacements[i][1]);
            }
            if (that.switchToNonStatic) {
                srcImage = srcImage.replace("static1.france-ioi.org", "concours1.castor-informatique.fr");
                srcImage = srcImage.replace("static2.france-ioi.org", "concours2.castor-informatique.fr");
                for(var i=0; i<window.config.imagesURLReplacementsNonStatic.length; i++) {
                    srcImage = srcImage.replace(window.config.imagesURLReplacementsNonStatic[i][0], window.config.imagesURLReplacements[i][1]);
                }
                that.newUrlImages[that.imagesToPreload[that.nbImagesLoaded]] = srcImage;
            }
            if(window.config.upgradeToHTTPS) {
                srcImage = srcImage.replace(/^http:/, "https:");
            }
            that.loadingImages[that.nbImagesLoaded].src = srcImage;
        } else {
            ImagesLoader.loadHandler();
        }
    },

    preload: function (contestFolder) {
        ImagesLoader.contestFolder = contestFolder;
        ImagesLoader.nbImagesToLoad = ImagesLoader.imagesToPreload.length;
        ImagesLoader.loadNextImage();
    },

    /* Updates the src attribute of images that couldnt be pre-loaded with the original url. */
    refreshImages: function () {
        $.each($("img"), function (i, elem) {
            var posContest = this.src.indexOf("contest");
            if (posContest < 0) {
                return;
            }
            if (ImagesLoader.newUrlImages[this.src] != undefined) {
                this.src = ImagesLoader.newUrlImages[this.src];
            }
        });
    }
};