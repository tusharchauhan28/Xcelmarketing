$(document).ready(function ($) {
    'use strict';


    /* ---------------------------------------------
         page  Prealoader
     --------------------------------------------- */
    // $(window).on('load', function () {
        // $("#loading-center").fadeOut();
        // $("#loading").delay(400).fadeOut("slow");
    // });




    /* ---------------------------------------------
        Sticky header
    --------------------------------------------- */
    $(window).on('scroll', function () {
        var scroll_top = $(window).scrollTop();

        if (scroll_top > 40) {
            $('.navbar').addClass('sticky');

        } else {
            $('.navbar').removeClass('sticky');
        }

    });


    /* ---------------------------------------------
        Slick
    --------------------------------------------- */

    var $pages = $('.caroussel-page-inner a');


    $('.img-carousel').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        speed: 600,
        autoplay: false,
        infinite: true,
        arrows: false,
        fade: true
    });
    $('.text-carousel').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 6000,
        infinite: true,
        arrows: false
    });


    $pages.on('click', function (e) {

        e.preventDefault();
        var index = $(this).index();
        $('.text-carousel').slick('slickGoTo', index);
        $('.text-carousel').slick('slickPause');

        $('.img-carousel').slick('slickGoTo', index);
        $('.img-carousel').slick('slickPause');
    });


    $('.testimonial-slider').slick({
        dots: false,
        speed: 600,
        autoplay: false,
        infinite: true,
        arrows: true,
        fade: true,
        prevArrow: "<button type='button' class='slick-prev pull-left'><i class='arrow_left' aria-hidden='true'></i></button>",
        nextArrow: "<button type='button' class='slick-next pull-right'><i class='arrow_right' aria-hidden='true'></i></button>"

    });
    
  $('.blog-post-slider').slick({
       dots: false,
        arrows: true,
        infinite: true,
        speed: 300, 
        slidesToShow: 2,
        slidesToScroll: 2,
        prevArrow: "<button type='button' class='slick-prev pull-left'><i class='arrow_left' aria-hidden='true'></i></button>",
        nextArrow: "<button type='button' class='slick-next pull-right'><i class='arrow_right' aria-hidden='true'></i></button>",
    responsive: [
    {
      breakpoint: 1024,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
      }
    },
    {
      breakpoint: 600,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1
      }
    },
    {
      breakpoint: 480,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1
      }
    }
 
  ]

    });

    
     $('.client-caroussel').slick({
       dots: false,
        arrows: false,
        infinite: true,
        speed: 300, 
        slidesToShow: 6,
        slidesToScroll: 6,
        prevArrow: "<button type='button' class='slick-prev pull-left'><i class='arrow_left' aria-hidden='true'></i></button>",
        nextArrow: "<button type='button' class='slick-next pull-right'><i class='arrow_right' aria-hidden='true'></i></button>",
    responsive: [
    {
      breakpoint: 1024,
      settings: {
        slidesToShow: 3,
        slidesToScroll: 3,
      }
    },
    {
      breakpoint: 600,
      settings: {
        slidesToShow: 2,
        slidesToScroll: 2
      }
    },
    {
      breakpoint: 480,
      settings: {
        slidesToShow:2,
        slidesToScroll: 2
      }
    }
 
  ]

    });
 
    /*--------------------
 MAGNIFIC POPUP IMAGE  JS
 ----------------------*/
    $('.modal-image').magnificPopup({
        type: 'image',
        removalDelay: 300,
        mainClass: 'mfp-with-zoom',
        gallery: {
            enabled: true
        },
        zoom: {
            enabled: true,

            duration: 300,
            easing: 'ease-in-out',

            opener: function (openerElement) {

                return openerElement.is('img') ? openerElement : openerElement.find('img');
            }
        }
    });

    /*--------------------
VenoBox
 ----------------------*/

    $('.modal-venobox').venobox({
        numeratio: true, // default: false
        infinigall: true,
        titleattr: 'data-title', // default: 'title'// default: false
    });

    /*----------------------------------------------------*/
    /*  VIDEO POP PUP
    /*----------------------------------------------------*/

    $('.video-modal').magnificPopup({
        type: 'iframe',

        iframe: {
            patterns: {
                youtube: {

                    index: 'youtube.com',
                    src: 'https://www.youtube.com/embed/7e90gBu4pas'

                }
            }
        }
    });
    /* ---------------------------------------------
     Back top page scroll up
     --------------------------------------------- */


    $.scrollUp({
        scrollText: '<i class=" arrow_carrot-up"></i>',
        easingType: 'linear',
        scrollSpeed: 900,
        animation: 'fade'
    });


    /* ---------------------------------------------
     WoW plugin
     --------------------------------------------- */

    new WOW().init({
        mobile: true,
    });

    /* ---------------------------------------------
     Smooth scroll
     --------------------------------------------- */

    $('a.section-scroll[href*="#"]:not([href="#"])').on('click', function (event) {
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') ||
            location.hostname == this.hostname) {

            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
            if (target.length) {
                // Only prevent default if animation is actually gonna happen
                event.preventDefault();
                $('html,body').animate({
                    scrollTop: target.offset().top
                }, 750);
                return false;
            }
        }
    });



    /*----------------------------------------
     Newsletter Subscribe
     --------------------------------------*/

    $(".subscribe-mail").ajaxChimp({
        callback: mailchimpCallRep,
        url: "mailchimp-post-url" //Replace this with your own mailchimp post URL. Just paste the url inside "".
    });

    function mailchimpCallRep(resp) {
        if (resp.result === "success") {
            $(".sucess-message").html(resp.msg).fadeIn(1000);
            $(".error-message").fadeOut(500);
        } else if (resp.result === "error") {
            $(".error-message").html(resp.msg).fadeIn(1000);
        }
    }

});