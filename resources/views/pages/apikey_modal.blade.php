<!-- Admin Update Modal -->
<div class="modal fade" id="updatePartnerModal" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="updatePartnerLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updatePartnerLabel">Update Partner</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form class="row">
          <input type="hidden" id="modal_id">
          <div class="col-md-12">
            <div class="form-group row">
              <label for="modal_name" class="col-sm-2 col-form-label">Name</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="modal_name">
              </div>
            </div>
            <div class="form-group row">
              <label for="modal_domain" class="col-sm-2 col-form-label">Domain</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="modal_domain">
              </div>
            </div>
            <div class="form-group row">
              <label for="modal_fee" class="col-sm-2 col-form-label">Fee</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="modal_fee">
              </div>
            </div>
            <div class="form-group row">
              <label for="modal_crypto_fee" class="col-sm-2 col-form-label">Crypto Fee</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="modal_crypto_fee">
              </div>
            </div>
            <div class="form-group row">
              <label for="modal_status" class="col-sm-2 col-form-label">Status</label>
              <div class="col-sm-10">
                <select class="form-control" id="modal_status">
                  <option value="1">Enable</option>
                  <option value="0">Disable</option>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <label for="modal_fee" class="col-sm-2 col-form-label">Api key</label>
              <div class="col-sm-10">
                <label type="text" class="form-control" id="modal_api_key" style="word-break: break-all"></label>
              </div>
            </div>
            <div class="modal-success form-group row" style="display: none;">
              <div class="col-md-12">
                <div class="alert alert-success" role="alert">
                  This is a success alert—check it out!
                </div>
              </div>
            </div>
            <div class="modal-failed form-group row" style="display: none;">
              <div class="col-md-12">
                <div class="alert alert-danger" role="alert">
                  This is a danger alert—check it out!
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="updatePartner()">Save changes</button>
      </div>
    </div>
  </div>
</div>
<script>
  function updatePartner(){

    let confirmed = window.confirm('Are you sure you want to update this partner?');
    if (!confirmed)
      return;

    let dataToSend = {
      id: $('#modal_id').val(),
      name: $('#modal_name').val(),
      domain: $('#modal_domain').val(),
      crypto_fee: $('#modal_crypto_fee').val(),
      fee: $('#modal_fee').val(),
      status: $('#modal_status').val(),
    };

    $.ajax({
      url: window.location.origin + '/admin/apikey-update-partner',
      type: 'POST',
      data: JSON.stringify(dataToSend),
      contentType: 'application/json',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if( response['code'] !== 200 ){
          $('.modal-success').hide();
          $('.modal-failed').show();
          $('.modal-failed .alert').html(response['message']);
        }
        else{
          let data = response['body'];
          $('.modal-success').show();
          $('.modal-failed').hide();
          $('.modal-success .alert').html(response['message']);
          $('#modal_api_key').text(data.api_key);

          let $rowToUpdate = $('td button#modal_update_' + data.id).closest('tr');
          $rowToUpdate.find('td').each(function() {
            if( $(this).hasClass('name'))
              $(this).html(data.name);
            if( $(this).hasClass('domain'))
              $(this).html(data.domain);
            if( $(this).hasClass('fee'))
              $(this).html(data.fee);
            if( $(this).hasClass('crypto_fee'))
              $(this).html(data.crypto_fee);
            if( $(this).hasClass('api-key')){
              $(this).html('<button class="trans-btn" data-bs-toggle="modal" data-bs-target="#apiKeyModal" onclick="getApiKey('+ data.id +')">\
                                <i class="fas fa-eye text-danger"></i>\
                            </button>');
            }
            if( $(this).hasClass('status')){
              if((data.status * 1) === 1 ){
                $(this).html('<span class="text-success">\
                                  <i class="fas fa-check-circle"></i> Enabled\
                              </span>');
              }
              else{
                $(this).html('<span class="text-danger">\
                                  <i class="fas fa-ban"></i> Disabled\
                              </span>');
              }
            }
            if( $(this).hasClass('update_at'))
              $(this).text(data.update_at);
          });

          getApiKey($('#modal_id').val());
        }
      },
      error: function(xhr, textStatus) {
        $('.modal-success').hide();
        $('.modal-failed').show();
        $('.modal-failed .alert').html(xhr.responseJSON.message);
      }
    });
  }
</script>

