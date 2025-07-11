<div class="direct-chat-msg">
    <div class="direct-chat-info clearfix">
        <span class="direct-chat-name float-left">{{ \App\Helpers\DataFormatHelper::getCommentTagName($comment->tag) }}</span>
        <span class="direct-chat-timestamp float-right">{{ $comment->created_at }}</span>
    </div>
    <div class="direct-chat-text {{ $comment->sticky ? 'sticky' : '' }}"
         style="margin: 5px 0 0 5px;">
         <div style="white-space: {{ str_contains($comment->comment, 'beBettor') ? 'pre-wrap' : 'normal' }};">{{ $comment->comment }}</div>
        <div class="tools">
            <i id='{{ $comment->id }}' style="cursor: pointer;"
               url='{{ $app['url_generator']->generate('admin.user-delete-comment', ['user' => $user->id, 'comment' => $comment->id ]) }}'
               class="fas fa-trash"></i>
            @if($comment->sticky && $can_unstick_comments)
                <i id='{{ $comment->id }}' style="cursor: pointer; float: right;" data-toggle="tooltip" title="Remove sticky"
                   url='{{ $app['url_generator']->generate('admin.user-unstick-comment', ['user' => $user->id, 'comment' => $comment->id ]) }}'
                   class="fa fa-unlock"></i>
            @endif
        </div>
    </div>
</div>

