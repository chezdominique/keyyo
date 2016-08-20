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
$(document).ready(function (e) {

    var tempoNotification = 1000;
    var isEnabled = 'disabled';
    var modalKeyyo = $('[data-remodal-id=modal]').remodal();

    // Appel Keyyo
    $('.keyyo_link').parent().attr('onclick', '').css('cursor', 'text');

    // Rappel modal number
    $('.numberCaller').click(function (e) {
        e.preventDefault();
        get_fb_rappel($(this).find('.linkRappel').attr('href'));
    });


    $('.keyyo_link').click(function (e) {
        e.preventDefault();
        var link = $(this).attr('href');
        $.ajax({
            url: link,
            type: 'GET',
            dataType: 'json'
        })
            .done(function (data) {
                alert(data.msg);
            })
            .fail(function (data) {
                alert('Erreur : KEYYO refuse l\'appel.');
            });
    });


    // change l'état du bouton notification au chargement de la page
    if ($.cookie('enableNotificationKeyyo') == 'enabled') {
        changeButton();
        isEnabled = $('#checkboxAppelsKeyyo').attr('title');
        link = $('#checkboxAppelsKeyyo').attr('url') + '&isEnabled=' + isEnabled;
        get_fb_complete(link);
    }

    // Lors du click sur le bouton, démarre la surveillance des nouveaux appels
    $('#checkboxAppelsKeyyo').click(function (e) {
        changeButton();
        isEnabled = $('#checkboxAppelsKeyyo').attr('title');
        link = $('#checkboxAppelsKeyyo').attr('url') + '&isEnabled=' + isEnabled;
        get_fb_complete(link);
    });


    // Change l'état du bouton de notification
    function toggleBouton() {
        $('#notifKeyyoCheck').toggleClass('hidden');
        $('#notifKeyyoRemove').toggleClass('hidden');
        $('#checkboxAppelsKeyyo').toggleClass('action-disabled').toggleClass('action-enabled');
    }

    // Change le cookie et l'attribut title du bouton de notification
    function changeButton() {
        toggleBouton();

        if ($('#checkboxAppelsKeyyo').prop('title') == 'enabled') {
            $('#checkboxAppelsKeyyo').prop('title', 'disabled');
            $.cookie('enableNotificationKeyyo', 'disabled');
        } else {
            $('#checkboxAppelsKeyyo').prop('title', 'enabled');
            $.cookie('enableNotificationKeyyo', 'enabled');
        }
    }

    // Affiche un numero en particulier
    function get_fb_rappel(href) {
        $.ajax({
            type: "GET",
            url: href,
            dataType: 'json'
        }).done(function (data) {
            nouvelAppel(data);
            if (modalKeyyo.getState() == 'closed') {
                modalKeyyo.open();
            }
        });

    }


    // Fait la requete cyclique pour savoir si il y a de nouveau appels
    function get_fb_complete(link) {

        if (isEnabled == 'enabled') {
            heureLastNotif = $("#checkboxAppelsKeyyo").attr('heureLastNotif');

            $.ajax({
                type: "GET",
                url: link,
                dataType: 'json',
                data: 'heureLN=' + heureLastNotif
            }).done(function (data) {
                displayNotification(link, data);
            });
        }
    }

    // Affiche les nouvelles notifications
    function displayNotification(link, data) {
        setTimeout(function () {
            get_fb_complete(link);
        }, tempoNotification);

        if (data.show == 'true') {
            heureLastNotif = data.heureServeur;
            $('#checkboxAppelsKeyyo').attr('heureLastNotif', heureLastNotif);
            nouvelAppel(data);
            if (modalKeyyo.getState() == 'closed') {
                modalKeyyo.open();
            }
        } else {
            heureLastNotif = data.heureServeur;
            $('#checkboxAppelsKeyyo').attr('heureLastNotif', heureLastNotif);
        }
    }

    $('#rappelModal').click(function (e) {
        modalKeyyo.open();
    })


    // Crée le contenu d'un nouvel appel qui sera intégré dans la fenetre modale
    function nouvelAppel(data) {
        d = new Date(data.heureServeur * 1000);
        heureAppel = d.getHours() + ' : ' + d.getMinutes();
        var idHeureServeur = data.heureServeur;
        var newRow = $('#newRowCall')
            .clone();

        newRow.find('#fermerAppel').attr('id', idHeureServeur).click(function (e) {
            newRow.slideUp("slow").remove();
            closeModal();
        });
        newRow.find('#caller').removeAttr('id').html(data.caller);
        newRow.find('#redirectingNumber').removeAttr('id').html(data.redirectingNumber);
        newRow.find('#callee').removeAttr('id').html(data.callee);
        newRow.find('#dateMessage').removeAttr('id').html(data.dateMessage);
        newRow.find('#message').attr('id', 'message' + idHeureServeur).html(data.message);

        var id_textarea = 'textarea' + idHeureServeur;
        newRow.find('#customer_comment_Modal').attr({
            'value': data.messageHistorique,
            'id': id_textarea
        });

        var id_contact = 'select' + idHeureServeur;
        newRow.find('#id_contactNewCall').attr({
            'id': id_contact
        });

        if (data.message == 'Numéro trouvé.') {

            var historique_contact = 'historique_contact' + idHeureServeur;
            newRow.find('#historique_contact').attr({
                'id': historique_contact
            });

            newRow.find('#callerName').removeAttr('id').html(data.callerName);
            newRow.find('#submitCustomerComment').removeAttr('id').attr({
                'href': data.linkPostComment,
                'id_customer': data.id_customer,
                'id_textearea': id_textarea,
                'id_contact': id_contact,
                'historique_contact': historique_contact,
                'data-dref' : data.dref
            }).click(function (e) {
                e.preventDefault();
                var link = $(this).attr('href');
                var id_customer = $(this).attr('id_customer');
                var comment = $('#' + $(this).attr('id_textearea')).val();
                var id_contact = $('#' + $(this).attr('id_contact')).val();
                var historique_contact = $('#' + $(this).attr('historique_contact')).attr('checked');
                var dref = $(this).attr('data-dref');

                $.ajax({
                    url: link,
                    type: 'GET',
                    data: {
                        'id_customer': id_customer,
                        'id_contact': id_contact,
                        'comment': comment,
                        'historique_contact': historique_contact,
                        'dref': dref
                    },
                    dataType: 'json'
                })
                    .done(function (data) {
                        messageDone(data.message, idHeureServeur);
                    })
                    .fail(function (data) {
                        messageFail(data.message, idHeureServeur)
                    });
            });
            newRow.find('#voirFicheClient').removeAttr('id').attr('href', data.linkCustomer);

            for (var histoMes in data.histoMessage) {
                newRow.find('#histoMessage').append(data.histoMessage[histoMes]);
            }
            newRow.find('#histoMessage').removeAttr('id');
        } else {

            newRow.find('#submitCustomerComment').removeAttr('id').attr({
                'href': data.linkPostComment,
                'id_customer': data.id_customer,
                'id_textearea': id_textarea,
                'id_contact': id_contact
            }).click(function (e) {
                e.preventDefault();
                var link = $(this).attr('href');
                var id_customer = $(this).attr('id_customer');
                var comment = $('#' + $(this).attr('id_textearea')).val();
                var id_contact = $('#' + $(this).attr('id_contact')).val();

                $.ajax({
                    url: link,
                    type: 'GET',
                    data: {
                        'id_customer': id_customer,
                        'id_contact': id_contact,
                        'comment': comment
                    },
                    dataType: 'json'
                })
                    .done(function (data) {
                        messageDone(data.message, idHeureServeur);
                    })
                    .fail(function (data) {
                        messageFail(data.message, idHeureServeur)
                    });
            });

            newRow.find('#historique_contact').parent().remove();
            newRow.find('#tableInformationNewRowCall').remove();
            newRow.find('#voirFicheClient').removeAttr('id').remove();
            newRow.find('#informationNewRowCall').removeClass('col-md-2', 'col-md-12').removeAttr('id');
        }

        newRow.appendTo('#mainModalKeyyo').slideDown().attr('id', idHeureServeur);
    }

    function messageDone(message, idHeureServeur) {
        if (message == 'ok') {
            $('#' + idHeureServeur).slideUp("slow").remove('#' + idHeureServeur);
            closeModal();
        } else if (message == '1') {
            $('#' + 'message' + idHeureServeur).html('<p class="bg-danger text-danger">Veuillez choisir un destinataire</p>');
        } else if (message == '2') {
            $('#' + 'message' + idHeureServeur).html('<p class="bg-danger text-danger">Erreur lors de l\'enregistrement</p>');
        } else {
            $('#' + 'message' + idHeureServeur).html('<p class="bg-danger text-danger">Erreur inconnue</p>');
        }
    }

    function closeModal() {
        if ($('#mainModalKeyyo').find('div').length == 0) {
            modalKeyyo.close();
        }
    }

    function messageFail(message, idHeureServeur) {
        $('#' + 'message' + idHeureServeur).html('<p class="bg-danger text-danger">Pas de réponse du serveur</p>');
    }

});
