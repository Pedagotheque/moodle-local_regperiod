<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Base class for unit tests for regperiod/classes/task/checktask.
 *
 * @package   local_regperiod
 * @copyright  IMT Lille Douai <https://imt-lille-douai.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Martin CORNU-MANSUY <martin@call-learning>
 */

namespace local_regperiod;

use advanced_testcase;
use core_user;
use local_regperiod\task\checktask;

/**
 * Unit tests for the checktask class of regperiod.
 *
 * @copyright  IMT Lille Douai <https://imt-lille-douai.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Martin CORNU-MANSUY <martin@call-learning>
 */
class checktask_test extends advanced_testcase {

    /**
     * Just sets up each test with the parent method and the function {@see advanced_testcase::resetAfterTest()}
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Tests if the method {@see checktask::get_all_registration_info()} works fine by creating all types of users
     * and see if the data of each user is collected properly.
     *
     * @return void
     * @covers \local_regperiod\task\checktask::get_all_registration_info
     */
    public function test_get_all_registration_info() {
        $users = $this->create_users_from_user_provider();
        $fieldsid = checktask::get_reg_fields_ids();
        $usersgot = checktask::get_all_registration_info($fieldsid['startfieldid'], $fieldsid['endfieldid']);
        $usersgot = array_values($usersgot);
        $numberofnoneregistration = 0;
        $userslength = count($users);
        $usersgotlength = count($usersgot);
        for ($index = 0; $index < $userslength && $index < $usersgotlength; $index++) {
            $usergot = $usersgot[$index];
            self::assertEquals($users[$index]["user"]->id, $usergot->id);

            if (!isset($users[$index]["user"]->startreg)) {
                $users[$index]["user"]->startreg = null;
            }
            if (!isset($users[$index]["user"]->endreg)) {
                $users[$index]["user"]->endreg = null;
            }

            if ($users[$index]["user"]->startreg) {
                self::assertNotEmpty($usergot->startreg);
                self::assertEquals($users[$index]["user"]->startreg, $usergot->startreg);
            } else {
                self::assertTrue(!$users[$index]["user"]->startreg && !$usergot->startreg);
            }

            if ($users[$index]["user"]->endreg) {
                self::assertNotEmpty($usergot->endreg);
                self::assertEquals($users[$index]["user"]->endreg, $usergot->endreg);
            } else {
                self::assertTrue(!$users[$index]["user"]->endreg && !$usergot->endreg);
            }
            self::assertNotNull($usergot->suspended);
            self::assertNotNull($usergot->deleted);
        }
        for ($index = 0; $index < $userslength; $index++) {
            if ($users[$index]["user"]->endreg == null && $users[$index]["user"]->startreg == null) {
                $numberofnoneregistration++;
            }
        }
        self::assertEquals(count($users) - $numberofnoneregistration, count($usersgot));

    }

    /**
     * Tests if the method {@see checktask::get_reg_info()} works properly by checking with each type of user if their information
     * is gotten properly.
     *
     * @dataProvider user_provider Provides each type of user
     * @param array $userinfo Information of a user provided by {@see checktask_test::user_provider()}
     * @return void
     * @covers \local_regperiod\task\checktask::get_reg_info
     */
    public function test_get_reg_info($userinfo) {
        global $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');
        $user = $this->getDataGenerator()->create_user();
        profile_save_data((object)array_merge(['id' => $user->id], $userinfo));
        $reginfo = checktask::get_reg_info($user->id);
        self::assertEquals($userinfo["profile_field_startreg"], $reginfo["startreg"]);
        self::assertEquals($userinfo["profile_field_endreg"], $reginfo["endreg"]);
    }

    /**
     * Tests if the {@see checktask::suspend_user()} works fine by adding some users and changing their suspend status
     * with {@see checktask::suspend_user()}. Then checks if the modification made effect.
     *
     * @param array $updates Some tests samples provided by {@see checktask_test::suspend_provider()}.
     * @return void
     * @dataProvider suspend_provider
     * @covers \local_regperiod\task\checktask::suspend_user
     */
    public function test_suspend_user($updates) {
        $suspended = $updates["suspended"];
        $user = $this->create_users()[0];
        checktask::suspend_user($user->id, $suspended);
        $userdata = checktask::get_suspended_deleted($user->id);
        self::assertEquals($suspended, (bool) $userdata->suspended);
    }

    /**
     * Tests if the {@see checktask::update_user_status()} methods works well. By calling it on all types of users
     * (all types of users are defined in the {@see checktask_test::user_provider()} method).
     *
     * @param array $userinfo a user sample provided by {@see checktask_test::user_provider()}.
     * @param bool $issuspended A boolean representing whether the user has to be suspended after the execution or not.
     * @dataProvider user_provider
     * @covers \local_regperiod\task\checktask::update_user_status
     */
    public function test_update_user_status($userinfo, $issuspended) {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $user = $this->getDataGenerator()->create_user();
        profile_save_data((object)array_merge(['id' => $user->id], $userinfo));
        $reginfo = checktask::get_reg_info($user->id);
        $user->startreg = $reginfo["startreg"];
        $user->endreg = $reginfo["endreg"];

        checktask::update_user_status($user);

        $user = core_user::get_user($user->id);

        self::assertEquals($issuspended, (bool) $user->suspended);
    }

    /**
     * Tests if the {@see checktask::execute()} methods works well.
     * By creating all types of user at once (using {@see checktask_test::create_users_from_user_provider()}),
     * calling {@see checktask::execute()} and checking if the method had the right effects for each user.
     *
     * @covers \local_regperiod\task\checktask::execute
     */
    public function test_execute() {
        $users = $this->create_users_from_user_provider();
        $task = new checktask();
        $task->execute();

        foreach ($users as $testsample) {
            self::assertEquals(
                $testsample["issuspended"],
                (bool) checktask::get_suspended_deleted($testsample["user"]->id)->suspended);
        }
    }

    /**
     * Uses {@see checktask_test::user_provider()} to create all types of users in the database and get an array of objects
     * with everything we need for our tests.
     * Note that this array will be as below :
     * <pre>
     *  $users[] = [
     *      "issuspended" => $issuspended,
     *      "user" => $newuser
     *  ];
     * </pre>
     *
     * @return array with all we need to test this plugin.
     */
    private function create_users_from_user_provider(): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $data = $this->user_provider();
        $users = [];
        foreach ($data as $userdata) {
            $user = $this->getDataGenerator()->create_user();
            profile_save_data((object)array_merge(['id' => $user->id], $userdata["userinfo"]));
            $newuser = core_user::get_user($user->id);
            $newuser->startreg = $userdata["userinfo"]["profile_field_startreg"];
            $newuser->endreg = $userdata["userinfo"]["profile_field_endreg"];
            $users[] = [
                "issuspended" => $userdata["issuspended"],
                "user" => $newuser
            ];
        }
        return $users;
    }

    /**
     * Data provider for checktask test.
     *
     * @return array[]
     */
    public function user_provider(): array {
        $currenttime = time();
        return [
          'Currently enrolled user' => [
              'userinfo' => [
                  'profile_field_startreg' => $currenttime - 1000,
                  'profile_field_endreg' => $currenttime + 1000
              ],
              'issuspended' => false
          ],
            'Previously enrolled user' => [
                'userinfo' => [
                    'profile_field_startreg' => $currenttime - 2000,
                    'profile_field_endreg' => $currenttime - 1000
                ],
                'issuspended' => true
            ],
            'Not yet enrolled user' => [
                'userinfo' => [
                    'profile_field_startreg' => $currenttime + 1000,
                    'profile_field_endreg' => $currenttime + 2000
                ],
                'issuspended' => true
            ],
            'Only endreg passed user' => [
                'userinfo' => [
                    'profile_field_startreg' => null,
                    'profile_field_endreg' => $currenttime - 2000
                ],
                'issuspended' => true
            ],
            'Only endreg not passed user' => [
                'userinfo' => [
                    'profile_field_startreg' => null,
                    'profile_field_endreg' => $currenttime + 2000
                ],
                'issuspended' => false
            ],
            'Only startreg passed user' => [
                'userinfo' => [
                    'profile_field_startreg' => $currenttime - 2000,
                    'profile_field_endreg' => null
                ],
                'issuspended' => false
            ],
            'Only startreg not passed user' => [
                'userinfo' => [
                    'profile_field_startreg' => $currenttime + 2000,
                    'profile_field_endreg' => null
                ],
                'issuspended' => true
            ],
            'No registration info user' => [
                'userinfo' => [
                    'profile_field_startreg' => null,
                    'profile_field_endreg' => null
                ],
                'issuspended' => false
            ]
        ];
    }

    /**
     * Provides some tests for {@see checktask_test::test_suspend_user()}.
     *
     * @return array An array of test cases
     */
    public function suspend_provider(): array {
        return [
            "not suspended, not deleted" => [
                "updates" => [
                    "suspended" => false,
                    "deleted" => false
                ]
            ],
            "not suspended, deleted" => [
                "updates" => [
                    "suspended" => false,
                    "deleted" => true
                ]
            ],
            "suspended, not deleted" => [
                "updates" => [
                    "suspended" => true,
                    "deleted" => false
                ]
            ],
            "suspended, deleted" => [
                "updates" => [
                    "suspended" => true,
                    "deleted" => true
                ]
            ],
        ];
    }

    /**
     * Creates a specified number of random users with random start and end registration date.
     *
     * @param int $nbusers The number of users we will create.
     * @return array An array containing all the users created.
     */
    private function create_users($nbusers = 1): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        for ($i = 0; $i < $nbusers; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $startreg = time() + rand(-2000, 1000);
            $endreg = $startreg + rand(1, 2000);
            profile_save_data((object) [
                'id' => $user->id,
                'profile_field_startreg' => $startreg,
                'profile_field_endreg' => $endreg
            ]);
            $user = core_user::get_user($user->id);
            $user->startreg = $startreg;
            $user->endreg = $endreg;
            $createdusers[] = $user;
        }
        return $createdusers;
    }
}
