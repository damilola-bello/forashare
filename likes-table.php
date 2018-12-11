<section class="profile-breakdown-section">
	<h3 class="section-header">
		<span>Like / Dislike Received</span>
	</h3>
	<table class="likes-table">
		<tr class="table-heading">
			<th></th>
			<th title="Likes" class="icon-box"><i class="fas fa-thumbs-up icon"></i></th>
			<th title="Dislikes" class="icon-box"><i class="fas fa-thumbs-down icon"></i></i></th>
		</tr>
		<tr>
			<td>Post</td>
			<td><span class='count'><?php echo $post_likes_count; ?></span></td>
			<td><span class='count'><?php echo $post_dislikes_count; ?></span></td>
		</tr>
		<tr>
			<td>Comment</td>
			<td><span class='count'><?php echo $comment_likes_count; ?></span></td>
			<td><span class='count'><?php echo $comment_dislikes_count; ?></span></td>
		</tr>
		<tr>
			<td>Reply</td>
			<td><span class='count'><?php echo $reply_likes_count; ?></span></td>
			<td><span class='count'><?php echo $reply_dislikes_count; ?></span></td>
		</tr>
	</table>
</section>

.likes-table {
  margin-left: 0.5rem;
  border-collapse: collapse;
  width: 100%;
  font-size: 0.85rem;
  .icon {
    font-size: 1.1rem;
    color: #0c7cef;
    transition: 0.3s;
    padding-bottom: 0.35rem;
    border-bottom: 1px dotted #333;
  }
  .icon-box {
    cursor: help;
    &:hover {
      .icon {
        color: #003e7c; 
      }
    }
  }
  td, th {
    border: 1px solid #f0f0f0;
    text-align: left;
    padding: 0.5rem;
  }
  td {
    color: #555;
  }
  tr:nth-child(even) {
    background-color: #fafafa;
  }
  tr:hover:not(.table-heading) {
    background-color: #f5f5f5;
    td {
      color: #000;
    }
  }
}


self.bgColor = function (char) {
  var colors = ["gray", "maroon", "olive", "teal", "fucshia", "purple", ""];
  //convert the alphabet to ASCII value
  var value = Number(char.toLowerCase().charCodeAt(0));
  //ASCII a-z is 97-122
  var color = "";
  //I used true in the swutch case because js uses the value passed in the switch case as the basis to compare, in this case any case that matches 'true'
  switch (true) {
    case value <= 105:
      color = "gray";
      break;
    case value <= 109:
      color = "maroon";
      break;
    case value <= 113:
      color = "olive";
      break;
    case value <= 117:
      color = "teal";
      break;
    case value <= 121:
      color = "fucshia";
      break;
    case value <= 124:
      color = "purple";
      break;
    
    default:
      color = "purple";
      break;
  }
  return color;
};