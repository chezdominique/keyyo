<div class="remodal" data-remodal-id="modal">
    <a data-remodal-action="close" class="remodal-close"></a>
    <h1>Appel en cours</h1>
    <div class="container-fluid" id="mainModalKeyyo">

    </div>
    <a data-remodal-action="confirm" class="remodal-confirm" href="#">Fermer</a>
</div>

<div id="newRowCall" class="row newRowCall">
    <div class="col-md-2 text-left informationNewCall">
        <table class="table">
            <thead>
            <tr>
                <th>Appel Entrant</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><strong>Appel du :</strong> <span  id="caller" class="pull-right"></span></td>
            </tr>
            <tr>
                <td><strong>Pour le :</strong><span  id="calle" class="pull-right"></span></td>
            </tr>
            <tr>
                <td><strong>Heure :</strong><span  id="heureAppel" class="pull-right"></span></td>
            </tr>
            <tr>
                <td><strong>Message :</strong><span  id="message" class="pull-right"></span></td>
            </tr>
            </tbody>
        </table>
        <button id="fermerAppel" class="remodal-confirm">Fermer</button>
    </div>
    <div class="col-md-6">
        <table class="table table-hover text-left">
            <thead>
            <tr>
                <th class="tableInformationNewCall">Date/Collaborateur</th>
                <th>Commentaires</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>(D. Lopez) 11/08/2016 16:25:13</td>
                <td>un autre commentaire</td>
            </tr>
            <tr>
                <td>(D. Lopez)11/08/2016 16:24:58</td>
                <td>un commentaire</td>
            </tr>
            <tr>
                <td>(D. Lopez)11/08/2016 16:24:58</td>
                <td>un commentaire</td>
            </tr>
            <tr>
                <td>(D. Lopez)11/08/2016 16:24:58</td>
                <td>un commentaire</td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-4 messagesNewCall">
        <div class="row">
            <form action="" method="post" id="sendComment">
                <div class="form-group">
                    <div class="col-md-12">
                        <textarea class="form-control textareaMessagesNewCall" name="customer_comment" id="customer_comment"></textarea>
                        <bouton type="submit" class="btn btn-default" name="submitCustomerComment">Ajouter un commentaire</bouton>
                    </div>
                    <input type="hidden" name="id_customer_com" value="84632">
                </div>
            </form>
        </div>
    </div>
</div>