<label for="select-levenshtein_distance">Levenshtein Distance</label>
<select name="levenshtein_distance" id="select-levenshtein_distance" class="form-control select2-class"
        style="width: 100%;" data-placeholder="" data-allow-clear="true">
    <option value="all">All</option>
    <option value="5" {{$params['levenshtein_distance'] == '5' ? "selected" : ''}}>5</option>
    <option value="10" {{$params['levenshtein_distance'] == '10' ? "selected" : ''}}>10</option>
    <option value="15" {{$params['levenshtein_distance'] == '15' ? "selected" : ''}}>15</option>
    <option value="20" {{$params['levenshtein_distance'] == '20' ? "selected" : ''}}>20</option>
    <option value="30" {{$params['levenshtein_distance'] == '30' ? "selected" : ''}}>30</option>
    <option value="50" {{$params['levenshtein_distance'] == '50' ? "selected" : ''}}>50</option>
</select>