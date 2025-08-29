<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Product Entries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
    <div class="container py-4">
      <h1 class="mb-4">Inventory Memo</h1>
      <form id="entry-form" class="row g-3 mb-4">
        <div class="col-md-4">
          <label for="product_name" class="form-label">Product name</label>
          <input type="text" class="form-control" id="product_name" name="product_name" required maxlength="255">
          <div class="invalid-feedback" data-field="product_name"></div>
        </div>
        <div class="col-md-4">
          <label for="quantity_in_stock" class="form-label">Quantity in stock</label>
          <input type="number" class="form-control" id="quantity_in_stock" name="quantity_in_stock" min="0" step="1" required>
          <div class="invalid-feedback" data-field="quantity_in_stock"></div>
        </div>
        <div class="col-md-4">
          <label for="price_per_item" class="form-label">Price per item</label>
          <input type="number" class="form-control" id="price_per_item" name="price_per_item" min="0" step="0.01" required>
          <div class="invalid-feedback" data-field="price_per_item"></div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-striped align-middle" id="entries-table">
          <thead>
            <tr>
              <th>Product name</th>
              <th>Quantity in stock</th>
              <th>Price per item</th>
              <th>Datetime submitted</th>
              <th>Total value number</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
          <tfoot>
            <tr>
              <th colspan="4" class="text-end">Sum total:</th>
              <th id="sum-total">0.00</th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      function clearValidation() {
        $('#entry-form .invalid-feedback').text('');
        $('#entry-form input').removeClass('is-invalid');
      }

      function formatMoney(n) { return Number(n).toFixed(2); }

      function renderRows(data, totalSum) {
        const tbody = $('#entries-table tbody');
        tbody.empty();
        data.forEach((e) => {
          const tr = $('<tr>').attr('data-id', e.id);
          tr.append(`<td class="product_name"><span class="v">${e.product_name}</span><input class="form-control form-control-sm d-none" value="${e.product_name}"></td>`);
          tr.append(`<td class="quantity_in_stock"><span class="v">${e.quantity_in_stock}</span><input type="number" min="0" step="1" class="form-control form-control-sm d-none" value="${e.quantity_in_stock}"></td>`);
          tr.append(`<td class="price_per_item"><span class="v">${formatMoney(e.price_per_item)}</span><input type="number" min="0" step="0.01" class="form-control form-control-sm d-none" value="${e.price_per_item}"></td>`);
          tr.append(`<td class="submitted_at">${e.submitted_at}</td>`);
          tr.append(`<td class="total_value">${formatMoney(e.total_value)}</td>`);
          tr.append(`<td class="actions"><button class="btn btn-sm btn-outline-secondary edit">Edit</button> <button class="btn btn-sm btn-success save d-none">Save</button> <button class="btn btn-sm btn-link cancel d-none">Cancel</button></td>`);
          tbody.append(tr);
        });
        $('#sum-total').text(formatMoney(totalSum));
      }

      function fetchEntries() {
        $.get('/api/entries')
          .done(function(resp) {
            renderRows(resp.data, resp.meta.total_sum);
          });
      }

      $('#entry-form').on('submit', function(e) {
        e.preventDefault();
        clearValidation();
        const payload = {
          product_name: $('#product_name').val(),
          quantity_in_stock: $('#quantity_in_stock').val(),
          price_per_item: $('#price_per_item').val()
        };
        $.ajax({
          url: '/api/entries',
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken },
          data: payload
        }).done(function(data) {
          $('#entry-form')[0].reset();
          // Optimistically insert new row without extra GET
          const tbody = $('#entries-table tbody');
          const e = data;
          const tr = $('<tr>').attr('data-id', e.id);
          tr.append(`<td class="product_name"><span class="v">${e.product_name}</span><input class="form-control form-control-sm d-none" value="${e.product_name}"></td>`);
          tr.append(`<td class="quantity_in_stock"><span class="v">${e.quantity_in_stock}</span><input type="number" min="0" step="1" class="form-control form-control-sm d-none" value="${e.quantity_in_stock}"></td>`);
          tr.append(`<td class="price_per_item"><span class="v">${formatMoney(e.price_per_item)}</span><input type="number" min="0" step="0.01" class="form-control form-control-sm d-none" value="${e.price_per_item}"></td>`);
          tr.append(`<td class="submitted_at">${e.submitted_at}</td>`);
          tr.append(`<td class="total_value">${formatMoney(e.total_value)}</td>`);
          tr.append(`<td class="actions"><button class="btn btn-sm btn-outline-secondary edit">Edit</button> <button class="btn btn-sm btn-success save d-none">Save</button> <button class="btn btn-sm btn-link cancel d-none">Cancel</button></td>`);
          tbody.prepend(tr);
          // Update sum
          const currentSum = parseFloat($('#sum-total').text()) || 0;
          $('#sum-total').text(formatMoney(currentSum + parseFloat(e.total_value)));
        }).fail(function(xhr) {
          if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            Object.keys(errors).forEach(function(field) {
              const msgs = errors[field];
              const input = $(`#${field}`);
              input.addClass('is-invalid');
              $(`#entry-form .invalid-feedback[data-field="${field}"]`).text(msgs.join(' '));
            });
          } else {
            alert('Error submitting form');
          }
        });
      });

      $('#entries-table').on('click', '.edit', function() {
        const tr = $(this).closest('tr');
        tr.find('td').each(function() {
          const td = $(this);
          if (td.find('input').length) {
            td.find('span.v').addClass('d-none');
            td.find('input').removeClass('d-none');
          }
        });
        tr.find('.edit').addClass('d-none');
        tr.find('.save,.cancel').removeClass('d-none');
      });

      $('#entries-table').on('click', '.cancel', function() {
        const tr = $(this).closest('tr');
        tr.find('td').each(function() {
          const td = $(this);
          if (td.find('input').length) {
            td.find('span.v').removeClass('d-none');
            td.find('input').addClass('d-none');
          }
        });
        tr.find('.edit').removeClass('d-none');
        tr.find('.save,.cancel').addClass('d-none');
      });

      $('#entries-table').on('click', '.save', function() {
        const tr = $(this).closest('tr');
        const id = tr.data('id');
        const payload = {
          product_name: tr.find('td.product_name input').val(),
          quantity_in_stock: tr.find('td.quantity_in_stock input').val(),
          price_per_item: tr.find('td.price_per_item input').val()
        };
        $.ajax({
          url: `/api/entries/${id}`,
          method: 'PUT',
          headers: { 'X-CSRF-TOKEN': csrfToken },
          data: payload
        }).done(function(data) {
          const e = data;
          const tr = $(`#entries-table tbody tr[data-id='${id}']`);
          // Adjust sum: subtract old total, add new total
          const oldTotal = parseFloat(tr.find('td.total_value').text()) || 0;
          const currentSum = parseFloat($('#sum-total').text()) || 0;
          const newTotal = parseFloat(e.total_value) || 0;
          $('#sum-total').text(formatMoney(currentSum - oldTotal + newTotal));
          // Update row
          tr.find('td.product_name span.v').text(e.product_name);
          tr.find('td.quantity_in_stock span.v').text(e.quantity_in_stock);
          tr.find('td.price_per_item span.v').text(formatMoney(e.price_per_item));
          tr.find('td.total_value').text(formatMoney(e.total_value));
          tr.find('td input').addClass('d-none');
          tr.find('td span.v').removeClass('d-none');
          tr.find('.edit').removeClass('d-none');
          tr.find('.save,.cancel').addClass('d-none');
        }).fail(function(xhr) {
          alert('Error updating entry: ' + xhr.status + ' ' + xhr.statusText);
        });
      });

      $(function() {
        fetchEntries();
      });
    </script>
  </body>
  </html>


