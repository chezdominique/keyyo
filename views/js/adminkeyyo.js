/**
 * AdminKeyyo File Doc Comment
 *
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Dominique <dominique@chez-dominique.fr>
 * @copyright 2007-2016 PrestaShop SA / 2011-2016 Dominique
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registred Trademark & Property of PrestaShop SA
 */
$(document).ready(function(e){
    $('.keyyo_link').parent().attr('onclick','').css('cursor','text');
    $('.keyyo_link').click(function(e){
        e.preventDefault();
        var link = $(this).attr('href');
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'json'
        })
            .done(function(data) {
                alert(data.msg);
            })
            .fail(function(data) {
                alert('Erreur : KEYYO refuse l\'appel.');
            });
    });


    $(document).ready(function() {
        $('#mytrigger').click(function(e) {
            $('#this_to_be_opened').toggle();
        });
    });


        // function get_fb_complete(){
        //     $('#footer').append('<li>get_fb() ran</li>');
        //     var feedback = $.ajax({
        //         type: "POST",
        //         url: "feedback.php",
        //         async: false
        //     }).complete(function(){
        //         setTimeout(function(){get_fb_complete();}, 1000);
        //     }).responseText;
        //
        //     $('div.feedback-box-complete').html('complete feedback');
        // }
        //
        // $(function(){
        //     get_fb_complete();
        // });


});