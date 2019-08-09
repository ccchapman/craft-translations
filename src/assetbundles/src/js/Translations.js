(function($) {

    if (typeof Craft.Translations === 'undefined') {
        Craft.Translations = {};
    }
    
    /**
     * Base Translations class
     */
    Craft.Translations = {
        trackJobProgressTimeout: null,
        job: null,

        init: function() {
        },
        trackJobProgressById: function(delay, force, params) {
            if (force && this.trackJobProgressTimeout) {
                clearTimeout(this.trackJobProgressTimeout);
                this.trackJobProgressTimeout = null;
            }

            if (delay === true) {
                // Determine the delay based on how long the displayed job info has remained unchanged
                var timeout = Math.min(60000, Craft.cp.displayedJobInfoUnchanged * 500);
                this.trackJobProgressTimeout = setTimeout($.proxy(this, '_trackJobProgressInternal', params), timeout);
            } else {
                this._trackJobProgressInternal(params);
            }
        },
        
        /**
         * 
         * @param {id, notice, url} params 
         */
        _trackJobProgressInternal: function(params) {
            Craft.queueActionRequest('queue/get-job-info?dontExtendSession=1', $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    this.trackJobProgressTimeout = null;

                    if (Craft.cp.jobInfo.length) {
                        let inQueue = false;
                        // Check to see if it matches our jobId
                        for (i = 0; i < Craft.cp.jobInfo.length; i++) {
                            var matches = false;
                            if (params.id == Craft.cp.jobInfo[i].id) {
                                matches = true;
                                inQueue = true;
                            }
                            
                            if ((i + 1) == Craft.cp.jobInfo.length && !inQueue) {
                                // Job is not in queue
                                if (this.job) {
                                    Craft.cp.displayNotice(Craft.t('app', `${params.notice}`));
            
                                    if (params.url && window.location.pathname.includes('translations/orders')) {
                                        window.location.href=Craft.getUrl(params.url);
                                    }    
                                }
                            }

                            if (matches) {
                                this.job = Craft.cp.jobInfo[i];

                                console.log(`Translation job progress: ${Craft.cp.jobInfo[i].progress}`);
                                // Check again after a delay
                                this.trackJobProgressById(true, false, params);
                            }
                        }
                    } else {
                        // Job is either completed or no jobs running
                        if (this.job) {
                            Craft.cp.displayNotice(Craft.t('app', `${params.notice}`));
    
                            if (params.url && window.location.pathname.includes('translations/orders')) {
                                window.location.href=Craft.getUrl(params.url);
                            }    
                        }
                    }
                }
            }, this));
        },
    };
    
    })(jQuery);