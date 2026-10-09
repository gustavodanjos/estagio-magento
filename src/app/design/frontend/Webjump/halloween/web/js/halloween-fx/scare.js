define(["jquery"], function ($) {
    "use strict";

    var SPAWN_MIN = 10000,
        SPAWN_MAX = 25000,
        VISIBLE_TIMEOUT = 30000,
        FLASH_DURATION = 600,
        scareImages = [],
        scareIndex = 0,
        spawnTimer,
        dismissTimer,
        $source,
        $pumpkin = null;

    function randomBetween(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function scheduleNext() {
        window.clearTimeout(spawnTimer);
        spawnTimer = window.setTimeout(
            spawn,
            randomBetween(SPAWN_MIN, SPAWN_MAX),
        );
    }

    function dismiss() {
        window.clearTimeout(dismissTimer);

        if ($pumpkin) {
            $pumpkin.remove();
            $pumpkin = null;
        }

        scheduleNext();
    }

    function nextScareImage() {
        var url = scareImages[scareIndex % scareImages.length];
        scareIndex = (scareIndex + 1) % scareImages.length;

        return url;
    }

    function flashScare() {
        if (!scareImages.length) {
            return;
        }

        var $flash = $(
            '<div class="scare-jumpscare" aria-hidden="true"><img alt="" /></div>',
        );

        $flash.find("img").attr("src", nextScareImage());
        $("body").append($flash);

        window.setTimeout(function () {
            $flash.remove();
        }, FLASH_DURATION);
    }

    function scare() {
        flashScare();

        $.ajax({
            url: $source.data("scare-url"),
            method: "POST",
        });

        dismiss();
    }

    function spawn() {
        if ($pumpkin) {
            return;
        }

        $pumpkin = $(
            '<button type="button" class="scare-pumpkin" ' +
                'aria-label="A pumpkin appeared on the page. Click it to get surprised."></button>',
        )
            .css({
                left: window.scrollX + Math.round((window.innerWidth * randomBetween(5, 85)) / 100),
                top: window.scrollY + Math.round((window.innerHeight * randomBetween(15, 80)) / 100),
            })
            .appendTo("body")
            .on("click", scare);

        dismissTimer = window.setTimeout(dismiss, VISIBLE_TIMEOUT);
    }

    return {
        init: function (options) {
            if (!options.enabled || !$("#scare-counter").length) {
                return;
            }

            $source = $("#scare-counter");
            scareImages = $source.data("scare-images") || [];
            scheduleNext();
        },
    };
});
