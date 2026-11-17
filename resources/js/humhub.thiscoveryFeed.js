humhub.module('thiscoveryFeed', function (module, require, $) {
    var event = require('event');
    var Widget = require('ui.widget').Widget;
    var loader = require('ui.loader');
    var string = require('util').string;
    var view = require('ui.view');
    var i18n = require('i18n');
    var user = require('user');
    var bound = false;

    var badgeTemplate = '<div id="streamUpdateBadge" class="animated bounceIn"><span class="badge text-bg-info" style="cursor:pointer"><i class="fa fa-arrow-circle-up"></i> {text}</span></div>';

    var root = function () {
        return $('.tc-feed-composer');
    };

    var mode = function () {
        if (root().find('.tc-feed-locked-guid').length) {
            return 'spaces';
        }
        return root().find('input[name="tc-feed-audience"]:checked').val() || 'spaces';
    };

    var allGuids = function () {
        try {
            return JSON.parse(root().attr('data-space-guids') || '[]');
        } catch (error) {
            return [];
        }
    };

    var guids = function () {
        var locked = root().find('.tc-feed-locked-guid').val();
        if (locked) {
            return [locked];
        }
        if (mode() === 'everyone') {
            return allGuids();
        }
        return root().find('.tc-feed-audience__list input:checked').map(function () {
            return this.value;
        }).get();
    };

    var showError = function (message) {
        var $error = $('#tc-feed-audience-error');
        if (!$error.length) {
            return;
        }
        if (message) {
            $error.text(message).prop('hidden', false);
        } else {
            $error.text('').prop('hidden', true);
        }
    };

    var syncAudience = function () {
        var everyone = mode() === 'everyone';
        root().find('[data-audience="everyone"]').prop('hidden', !everyone);
        root().find('[data-audience="spaces"]').prop('hidden', everyone);
        showError('');
    };

    var composerForm = function () {
        return root().find('#contentFormBody form');
    };

    var pointSubmit = function () {
        var url = root().attr('data-publish-url');
        if (!url) {
            return;
        }
        var $button = $('#post_submit_button');
        $button.attr('data-action-click-url', url);
        $button.data('action-click-url', url);
        composerForm().attr('action', url);
    };

    var rememberTargets = function ($form) {
        if (!$form || !$form.length) {
            return;
        }
        $form.find('[name="feedAudience"], [name="feedSpaces[]"]').remove();
        $('<input>', {type: 'hidden', name: 'feedAudience', value: mode()}).appendTo($form);
        guids().forEach(function (guid) {
            $('<input>', {type: 'hidden', name: 'feedSpaces[]', value: guid}).appendTo($form);
        });
    };

    var init = function () {
        pointSubmit();
        syncAudience();

        root().find('input[name="tc-feed-audience"]').off('change.thiscoveryFeed').on('change.thiscoveryFeed', function () {
            syncAudience();
            rememberTargets(composerForm());
        });
        root().find('.tc-feed-audience__list input').off('change.thiscoveryFeed').on('change.thiscoveryFeed', function () {
            showError('');
            rememberTargets(composerForm());
        });
        rememberTargets(composerForm());

        if (!bound) {
            bound = true;
            document.addEventListener('click', function (clickEvent) {
                var button = clickEvent.target && clickEvent.target.closest ? clickEvent.target.closest('#post_submit_button') : null;
                if (!button || !button.closest('.tc-feed-composer')) {
                    return;
                }
                if (mode() === 'spaces' && !guids().length) {
                    clickEvent.preventDefault();
                    clickEvent.stopImmediatePropagation();
                    showError(root().attr('data-choose-space') || '');
                    return;
                }
                showError('');
                pointSubmit();
                rememberTargets(composerForm());
            }, true);

            event.on('humhub:modules:content:live:NewContent.thiscoveryFeed', function (evt, events) {
                var stream = Widget.instance($('#thiscovery-feed-stream'));
                if (!stream || !stream.$ || !stream.$.length || !events || !events.length) {
                    return;
                }
                if (feedHasUpdate(stream, events)) {
                    showUpdateBadge(stream);
                }
            });

            event.on('humhub:content:beforeSubmit.thiscoveryFeed', function (evt, formWidget) {
                if (!formWidget || !formWidget.$ || !formWidget.$.closest('.tc-feed-composer').length) {
                    return;
                }
                pointSubmit();
                rememberTargets(formWidget.$.find('form'));
            });
        }
    };

    var feedSpaceGuids = function () {
        try {
            return JSON.parse($('#thiscovery-feed-stream').attr('data-feed-spaces') || '[]');
        } catch (error) {
            return [];
        }
    };

    var feedHasUpdate = function (stream, events) {
        var spaces = feedSpaceGuids();
        var found = false;
        $.each(events, function (index, liveEvent) {
            var data = liveEvent.data || {};
            if (data.streamChannel && data.streamChannel !== 'default') {
                return;
            }
            if (!data.insert || !data.sguid || spaces.indexOf(data.sguid) === -1) {
                return;
            }
            if (stream.entry && stream.entry(data.contentId)) {
                return;
            }
            if (stream.submitLock && data.originator === user.guid()) {
                return;
            }
            found = true;
        });
        return found;
    };

    var showUpdateBadge = function (stream) {
        if (stream.submitLock || $('#streamUpdateBadge').length) {
            return;
        }

        var $badge = $(string.template(badgeTemplate, {
            text: i18n.t('ContentModule.base', 'New Updates Available!')
        }));
        var appendToStream = function () {
            stream.$.prepend($badge.css({position: '', display: 'block'}));
            $badge.data('appended', true);
        };

        $('body').append($badge);

        var topOffset = view.getContentTop();
        $badge.css({
            position: 'fixed',
            top: (topOffset + 20) + 'px',
            left: (stream.$.offset().left + (stream.$.width() / 2) - ($badge.find('span').width() / 2)) + 'px',
            display: 'inline-block',
            'text-align': 'center',
            'z-index': 9999,
            'margin-top': '15px',
            'margin-bottom': '15px'
        }).on('click', function () {
            var load = function () {
                loader.set($badge, {css: {padding: '4px'}});
                stream.loadUpdate().finally(function () {
                    $badge.remove();
                });
            };

            if ($badge.data('appended')) {
                load();
            } else {
                $('html').animate({scrollTop: 0}, 'slow', function () {
                    appendToStream();
                    load();
                });
            }
        });

        if (($(window).scrollTop() + topOffset - stream.$.position().top) < 0) {
            appendToStream();
        }
    };

    module.export({
        init: init,
        initOnPjaxLoad: true
    });
});
