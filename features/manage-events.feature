Feature: Creating events
    In order to create events
    As a user
    I need to be able to configure the event's schedule

    Background:
        Given I have a vanilla wordpress installation
            | name          | email                   | username | password |
            | BDD WordPress | test.user@wordpress.dev | admin    | test     |

        And there are plugins
            | plugin                              | status  |
            | event-organiser/event-organiser.php | enabled |
    
        And I am logged in as "admin" with password "test"


    @javascript
    Scenario: Hovering over Profiles menu (RQEP.015)
        Given I am on "/wp-admin/index.php"
        When I focus on the element "#menu-posts-event a"
        Then I should see an "#menu-posts-event .wp-submenu li.wp-first-item" element
        And I should see "All Events" in the "#menu-posts-event .wp-submenu" element
        And I should see "Add New" in the "#menu-posts-event .wp-submenu" element
        And I should see "Categories" in the "#menu-posts-event .wp-submenu" element
        And I should see "Venues" in the "#menu-posts-event .wp-submenu" element
        And I should see "Calendar View" in the "#menu-posts-event .wp-submenu" element


    Scenario: Events menu placement
        When I go to "/wp-admin/index.php"
        And I should see the following in the repeated "#adminmenu > li a .wp-menu-name" element
            | text       |
            | Dashboard  |
            | Posts      |
            | Events     |            
            | Media      |
            | Pages      |
            | Comments 0 |
            | Appearance |
            | Plugins 0  |
            | Users      |
            | Tools      |
            | Settings   |

    Scenario: Navigating to All Events screen
        When I go to "/wp-admin/index.php"
        And I follow "Events"
        Then I should see "All Events"

