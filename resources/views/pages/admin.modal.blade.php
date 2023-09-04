<!-- Admin Update Modal -->
<div class="modal fade" id="updateModal_{{ $data->id }}" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updateModalLabel">Update Entry</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form>
          <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" id="name_{{ $data->id }}" value="{{ $data->name }}">
          </div>
          <div class="form-group">
            <label for="domain">Domain</label>
            <input type="text" class="form-control" id="domain_{{ $data->id }}" value="{{ $data->domain }}">
          </div>
          <div class="form-group">
            <label for="fee">Fee</label>
            <input type="text" class="form-control" id="fee_{{ $data->id }}" value="{{ $data->fee }}">
          </div>
          <input type="hidden" id="api_key_{{ $data->id }}" value="{{ $data->api_key }}">
          <div class="form-group">
            <label for="status">Status</label>
            <select class="form-control" id="status_update_{{ $data->id }}">
              <option value="enable" {{ $data->status == 'enable' ? 'selected' : '' }}>Enable</option>
              <option value="disable" {{ $data->status == 'disable' ? 'selected' : '' }}>Disable</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="updateEntry({{ $data->id }})">Save changes</button>
      </div>
    </div>
  </div>
</div>
