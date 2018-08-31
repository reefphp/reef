<?php

namespace Reef\Filesystem {
	function move_uploaded_file($s_tmpName, $s_dest) {
		return copy($s_tmpName, $s_dest);
	}
}
