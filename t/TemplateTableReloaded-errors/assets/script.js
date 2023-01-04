$(function() {
  var query = location.search;
  query = query.length ? query + '&' : '?'
  query += 'action=purge';

  $('table.ttable th:last-child').append($('<a>').attr({
    'href': query,
    'title': 'Clear page cache (refresh all tables on this page)'
  }));
});
