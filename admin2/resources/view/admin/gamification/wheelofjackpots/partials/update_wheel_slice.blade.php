<tr>
    <td class="orderNum">
	<span class="fa fa-sort"></span>&nbsp;&nbsp;
	{{ $slice->sort_order + 1 }}
    </td>
    <td>
        @foreach ($awards as $award)
            <a href="#" data-type="select2" data-pk="{{ $slice->id }}" data-award_alias="{{ $award->alias }}" data-name='award_id' data-value="{{ $award->id }}" data-title="Select award" class="editable editable-click pUpdateAwards" data-original-title="" title="">
	        {{ $award->alias }}
	    </a>
            &nbsp;
            (<a href="#" style="color: #f55;" onclick="removeAwardFromSlice({{ $slice->id }}, '{{ $award->alias }}')">X</a>)
            &nbsp;&nbsp;
        @endforeach
        @if ((substr_count($slice->award_id, ",") + 1) < 1000 && p('woj.add.slice.award'))
            <a href="#" data-type="select2" data-pk="{{ $slice->id }}" data-name='award_id' data-value="" data-title="Add Award" class="editable editable-click pUpdateAwards" data-original-title="" title="">
                Add Award
            </a>
        @endif
    </td>
    <td>
		@if(p('woj.slice.probability'))
	        <a class="pUpdate editable editable-click probability" id="probability_slice_{{ $key+1 }}" data-pk="{{ $slice->id }}" data-name='probability' data-title='Set probability'>{{ $slice->probability }}</a>
	    @else
	        {{ $slice->probability }}
	    @endif
    </td>
    <td role="group" aria-haspopup="true">
		@if(p('woj.delete.slice'))
            <button
                class="btn btn-sm btn-link action-set-btn p-0" id="delete_slice_{{ $key+1 }}"
                onclick="deleteSlice('{{ $slice->id }}', 'delete_slice_{{ $key+1 }}', '{{ $app['url_generator']->generate('wheelofjackpots-delete-slice', ['slice_id' => $slice->id]) }}')"
                data-value="{{$slice->id}}">
                    <i class="fas fa-trash"></i>
            </button>
        @endif
    </td>
</tr>
