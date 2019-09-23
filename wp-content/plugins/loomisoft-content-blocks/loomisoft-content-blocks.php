<?php
/*
Plugin Name: Reusable Content & Text Blocks by Loomisoft
Plugin URI:  http://www.loomisoft.com/reusable-content-text-blocks-wordpress-plugin/
Description: Loomisoft's Reusable Content & Text Blocks plugin allows you to define modular and repeated blocks of text and other content and place them within pages, posts, sidebars, widgetised areas or anywhere on your site via shortcodes, via the provided widget or via PHP. The plugin is compatible with WPBakery's Page Builder (formerly known as Visual Composer), Avada's Fusion Builder, Beaver Builder and SiteOrigin Page Builder, which means that embedded blocks can have a richer range of elements, layout and styling.
Version:     1.4.3
Author:      Loomisoft
Author URI:  http://www.loomisoft.com/
License:     GPLv3 or later
*/

/*
Copyright (c) 2017 Loomisoft (www.loomisoft.com). All rights reserved.

The Loomisoft Content Blocks plugin is distributed under the GNU General Public License, Version 3 or later.
You should have received a copy of the GNU General Public License along with the Loomisoft Content Blocks
plugin files. If not, see <http://www.gnu.org/licenses/>.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

defined( 'ABSPATH' ) or die();

define( 'LS_CB_PLUGIN', __FILE__ );
define( 'LS_CB_PLUGIN_PATH', plugin_dir_path( LS_CB_PLUGIN ) );

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
require_once( LS_CB_PLUGIN_PATH . 'includes/ls_cb_main.php' );
require_once( LS_CB_PLUGIN_PATH . 'includes/ls_cb_widget.php' );

ls_cb_main::start( LS_CB_PLUGIN );

function ls_content_block_by_id( $id = false, $para = false, $vars = array() ) {
	return ls_cb_main::get_block_by_id( $id, $para, $vars );
}

function ls_content_block_by_slug( $slug = false, $para = false, $vars = array() ) {
	return ls_cb_main::get_block_by_slug( $slug, $para, $vars );
}
