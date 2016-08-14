<div class="remodal" data-remodal-id="modal">
    <a data-remodal-action="close" class="remodal-close"></a>
    <h1>Appel en cours</h1>
    <div class="container-fluid" id="mainModalKeyyo">
    </div>
    <button data-remodal-action="cancel" class="remodal-cancel">Fermer</button>
</div>

<div id="newRowCall" class="row newRowCall">
    <h2 class="text-left" id="callerName"></h2>
    <div id="informationNewRowCall" class="col-md-3 text-left informationNewCall">
        <table class="table">
            <thead>
            <tr>
                <th><strong></strong></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>Appel du :</strong> <span id="caller" class="pull-right"></span></td>
            </tr>
            <tr>
                <td><strong>Pour le :</strong><span id="callee" class="pull-right"></span></td>
            </tr>
            <tr>
                <td><strong>Date :</strong><span id="dateMessage" class="pull-right"></span></td>
            </tr>
            <tr>
                <td><strong>Message :</strong><span id="message" class="pull-right"></span></td>
            </tr>
            </tbody>
        </table>
        <div id="commentaireNewRowCall" class="messagesNewCall">
            <div class="row">
                <form action="" method="post" id="sendCommentModal">
                    <div class="form-group">
                        <div class="col-md-12">
                            <textarea class="form-control textareaMessagesNewCall" name="customer_comment"
                                      id="customer_comment_Modal"></textarea>
                            <button type="submit" class="btn btn-info input-block-level form-control" name="submitCustomerComment">Ajouter un
                                commentaire
                            </button>
                        </div>
                        <input type="hidden" name="id_customer_com" value="84632">
                    </div>
                </form>
            </div>
        </div>

        <a id="voirFicheClient" href="#" class="remodal-confirm remodal-confirm-link" role="button">Voir fiche
            client</a>
        <button href="#" id="fermerAppel" class="remodal-cancel remodal-cancel-link">Fermer</button>
    </div>
    <div id="tableInformationNewRowCall" class="col-md-9">
        <table class="table table-hover text-left">
            <thead>
            <tr>
                <th class="tableInformationNewCall">Date/Collaborateur</th>
                <th>Historique contact</th>
            </tr>
            </thead>
            <tbody id="histoMessage">

            </tbody>
        </table>
    </div>
</div>