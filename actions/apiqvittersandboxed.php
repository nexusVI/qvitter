<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * List all sandboxed users on the instance
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  API
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Jeffery To <jeffery.to@gmail.com>
 * @author    Zach Copley <zach@status.net>
 * @author    Hannes Mannerheim <h@nnesmannerhe.im>
 * @copyright 2009 StatusNet, Inc.
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://www.gnu.org/software/social/
 */

if (!defined('GNUSOCIAL')) {
    exit(1);
}

/**
 *
 * @category API
 * @package  GNUsocial
 * @author   Craig Andrews <candrews@integralblue.com>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Jeffery To <jeffery.to@gmail.com>
 * @author   Zach Copley <zach@status.net>
 * @author   Hannes Mannerheim <h@nnesmannerhe.im>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://www.gnu.org/software/social/
 */
class ApiQvitterSandboxedAction extends ApiPrivateAuthAction
{
    var $profiles = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    protected function prepare(array $args=array())
    {
        parent::prepare($args);

        $this->profiles = $this->getProfiles();

        return true;
    }

    /**
     * Handle the request
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */
    protected function handle()
    {
        parent::handle();

        // XXX: RSS and Atom

        switch($this->format) {
        case 'xml':
            $this->showTwitterXmlUsers($this->profiles);
            break;
        case 'json':
            $this->showJsonUsers($this->profiles);
            break;
        default:
            $this->clientError(
                // TRANS: Client error displayed when coming across a non-supported API method.
                _('API method not found.'),
                404,
                $this->format
            );
            break;
        }
    }

    /**
     * Fetch the sandboxed profiles
     *
     * @return array $profiles list of profiles
     */
    function getProfiles()
    {
        $profiles = array();

        $profile = $this->getSandboxed(
            ($this->page - 1) * $this->count,
            $this->count);

        while ($profile->fetch()) {
            $profiles[] = clone($profile);
        }

        return $profiles;
    }

    /**
     * Fetch the sandboxed profiles from DB
     *
     * @return array $profiles list of profiles
     */

    function getSandboxed($offset=null, $limit=null)   // offset is null because DataObject wants it, 0 would mean no results
    {

        $profiles = new Profile();
        $profiles->joinAdd(array('id', 'profile_role:profile_id'));
        $profiles->whereAdd(sprintf('profile_role.role = \'%s\'', Profile_role::SANDBOXED));
        $profiles->orderBy('profile_role.created DESC');
        $profiles->limit($offset, $limit);
        $profiles->find();

        return $profiles;
    }

    /**
     * Is this action read only?
     *
     * @param array $args other arguments
     *
     * @return boolean true
     */
    function isReadOnly($args)
    {
        return true;
    }

    /**
     * When was this list of profiles last modified?
     *
     * @return string datestamp of the lastest profile
     */
    function lastModified()
    {
        if (!empty($this->profiles) && (count($this->profiles) > 0)) {
            return strtotime($this->profiles[0]->created);
        }

        return null;
    }

    /**
     * An entity tag for this list
     *
     * Returns an Etag based on the action name, language
     * and timestamps of the first and last profile
     *
     * @return string etag
     */
    function etag()
    {
        if (!empty($this->profiles) && (count($this->profiles) > 0)) {

            $last = count($this->profiles) - 1;

            return '"' . implode(
                ':',
                array($this->arg('action'),
                      common_user_cache_hash($this->auth_user),
                      common_language(),
                      strtotime($this->profiles[0]->created),
                      strtotime($this->profiles[$last]->created))
            )
            . '"';
        }

        return null;
    }
}
