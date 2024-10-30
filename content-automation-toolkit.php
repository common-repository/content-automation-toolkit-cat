<?php
/*
Plugin Name: Content Automation Toolkit (CAT)
Version: 1.0.0
Description: The Content Automation Toolkit (CAT) is a powerful plugin for WordPress that streamlines the content creation process, saving you time and effort. This plugin taps into the power of OpenAI’s ChatGPT-3 system to generate entire articles based on any topic you input. CAT is also great for brainstorming and will help you select SEO optimized article titles, and is also smart enough to output content preformatted with optimized headers and sub-headings. You’ll also love the Social Helper feature which generates short summaries of any blog post along with recommended hashtags and a direct post link. Everything you need to help streamline your social media promotions. Simply enter your own API key available for free from OpenAI and start generating content today!
Author: BP MediaWorks
Author URI: https://bpmediaworks.com
Donate link: https://www.paypal.com/donate/?hosted_button_id=FDAJFP6ZJJFUE
Tags: content, automation, AI, artificial intelligence, blog, post, posting, automate, social media, generate, generator, chatgpt, openai, gpt, natural language
Minimum WordPress Version: 4.7
Tested Up To: 6.1.1
License: GPLv3
*/

function catai_add_admin_menu() {
    add_menu_page(
        'Content Automation Toolkit',
        'Content Automation Toolkit',
        'manage_options',
        'catai-settings',
        'catai_settings_page',
        plugin_dir_url(__FILE__) . 'cat-head.svg',
        20
    );
    add_submenu_page(
        'catai-settings',
        'Settings',
        'Settings',
        'manage_options',
        'catai-settings',
        'catai_settings_page'
    );
    add_submenu_page(
        'catai-settings',
        'Article Generator',
        'Article Generator',
        'manage_options',
        'catai-generator',
        'catai_generator_page'
    );
    add_submenu_page(
        'catai-settings',
        'Social Helper',
        'Social Helper',
        'manage_options',
        'catai-social-helper',
        'catai_social_helper_page'
    );
    add_submenu_page(
        'catai-settings',
        'FAQ',
        'FAQ',
        'manage_options',
        'catai-faq',
        'catai_faq_page'
    );
}

add_action( 'admin_menu', 'catai_add_admin_menu' );

//Start of Plugin Settings Page

function catai_settings_page() {
    ?>
<div class="catsettingsform">
    <h1>Content Automation Toolkit - Settings</h1>
    <?php settings_errors(); ?>
    <form method="post" action="options.php" class="catapisettingsform">
    <?php
        settings_fields( 'catai_settings_group' );
        do_settings_sections( 'content-automation-toolkit' );
        submit_button();
    ?>
</form>
</div>
<?php
}

function catai_add_settings_section() {
add_settings_section(
'catai_settings_section',
'OpenAI API Settings',
'catai_settings_section_cb',
'content-automation-toolkit'
);
}
add_action( 'admin_init', 'catai_add_settings_section' );

function catai_settings_section_cb() {
    echo '<div class="catai-generated-message">Enter your OpenAI API key to use Content Automation Toolkit. If you don\'t have one, you can <a href="https://beta.openai.com/signup/api-key" target="_blank">sign up for one here</a>.</div>';
    }

function catai_add_settings_field() {
add_settings_field(
'catai_api_key',
'API Key',
'catai_api_key_cb',
'content-automation-toolkit',
'catai_settings_section'
);
register_setting( 'catai_settings_group', 'catai_api_key', 'catai_validate_api_key' );
}
add_action( 'admin_init', 'catai_add_settings_field' );

function catai_api_key_cb() {
?>
<input class="regular-text" type="text" name="catai_api_key" placeholder="Enter your OpenAI API key here" value="<?php echo sanitize_text_field(get_option( 'catai_api_key' )); ?>">

<?php
}

function catai_validate_api_key( $api_key ) {
    $api_key = sanitize_text_field($api_key);
    if ( empty( $api_key ) ) {
        add_settings_error(
            'catai_api_key',
            'catai_api_key_error',
            'API Key field is required',
            'error'
        );
        return '';
    }

    $validation_url = esc_url_raw( 'https://api.openai.com/v1/engines/davinci/completions' );
    $data = array(
        'prompt' => sanitize_text_field( 'What is the capital of France?' ),
    );
    
    $headers = array(
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $api_key
    );    

    $response = wp_remote_post( $validation_url, array(
        'headers' => $headers,
        'body'    => wp_json_encode($data),
    ) ); 

    $response_code = wp_remote_retrieve_response_code( $response );

    if ( is_wp_error( $response ) || $response_code !== 200 ) {
        add_settings_error(
            'catai_api_key',
            'catai_api_key_error',
            'Invalid API Key',
            'error'
        );
        return '';
    }

    add_settings_error(
        'catai_api_key',
        'catai_api_key_success',
        'API Key is valid',
        'updated'
    );
    return sanitize_text_field( $api_key );
}

//End of Plugin Settings Page

//Start of code for the article generating page

function catai_generator_page() {

    $api_key = get_option( 'catai_api_key' );
    if ( empty( $api_key ) ) {
        echo '<div class="catai-article-generator">';
        echo '<h1>Content Automation Toolkit - Article Generator</h1>';
        echo '<p class="noapimessage">You need to enter a valid OpenAI API key before using the Article Generator. Please visit the <a href="admin.php?page=catai-settings">settings page</a> or <a href="https://beta.openai.com/signup/" target="_blank">signup for a new key</a>.</p>';
        echo '</div>';
        } else {

  ?>

<div class="catai-article-generator">
  <h1>Content Automation Toolkit - Article Generator</h1>
    <form method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" id="catai_titles_form" class="catai-form">
    <label for="topic">Please show me some potential article titles about:</label><br>
    <input type="text" id="topic" name="topic" placeholder="Enter a topic here">
    <br><input type="submit" id="catai_titles_submit" class="catai-generate-btn" value="Generate Titles">
  </form>
</div>
  
  <?php
        if ( isset( $_POST['topic'] ) ) {
            $topic = sanitize_text_field( $_POST['topic'] );
            $api_key = get_option('catai_api_key');
            $topic = sanitize_text_field( $_POST['topic'] );
            $prompt = 'Please generate 25 SEO optimized article titles about ' . esc_html($topic) . ' without numbers at the begining of the title.';
            $temperature = 0.7;
            $max_tokens = 4000;
            $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';
            
            $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key
                ),
                'body' => json_encode( array(
                    'prompt' => $prompt,
                    'temperature' => $temperature,
                    'max_tokens' => $max_tokens
                )),
                'timeout' => 60000
            ) );

            if( is_wp_error( $response ) ) {
                echo 'Error: ' . esc_html( $response->get_error_message() );
            } else {

                $result = json_decode( wp_remote_retrieve_body( $response ), true );
                $titles = preg_replace("/^\d+\.\s/", "", $result['choices'][0]['text']);
                echo '<h2>Potential Article Titles</h2>';
                echo '<form method="post" action="'. esc_url( $_SERVER['REQUEST_URI'] ) .'" class="catai-form">';
                echo '<div class="catai-title-select-container">';
                echo '<label for="title" class="catai-custom-label">Please select one of the titles: </label>';
                echo '<div class="title-select">';
                echo '<select id="title" name="title" style="width:100%;">';
                $titles = explode("\n", $titles);
                foreach($titles as $title) {
                echo '<option value="'.esc_html(trim($title)).'">'.esc_html($title).'</option>';
                }
                echo '</select>';
                echo '</div>';
                echo '<div class="generate-button">';
                echo '<input type="submit" value="Generate Article" class="catai-generate-btn">';
                echo '</div>';
                echo '</div>';
                echo '</form>';

            }
        }
        if (isset($_POST['title']) ) {
            $title = sanitize_text_field( $_POST['title'] );
            $api_key = get_option('catai_api_key');
            $prompt = 'Please write me a blog post about: ' . $title . ' between 2100 and 2400 words and with SEO optimized headings and subheadings before each paragraph of the article content as appropriate and wrapped in html tags for H2 and H3 as appropriate but do not use H1 tags and ensure the first heading of the article is different from the title of the article';
            $temperature = 0.7;
            $max_tokens = 4000;
            $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';
        
        $response = wp_remote_post( $url, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode( array(
                'prompt' => $prompt,
                'temperature' => $temperature,
                'max_tokens' => $max_tokens
            )),
            'timeout' => 60000
        ) );
        
                if( is_wp_error( $response ) ) {
                    echo 'Error: ' . esc_html( $response->get_error_message() );
                } else {

                    $result = json_decode( wp_remote_retrieve_body( $response ), true );
                    $article = $result['choices'][0]['text'];
                    $title = preg_replace("/^\d+\.\s/", "", $title);
                    $post_id = wp_insert_post( array(
                        'post_title' => $title,
                        'post_content' => wp_kses_post( $article ),
                        'post_status' => 'draft',
                    ) );
                    echo '<div class="catai-generated-message">Article generated and saved as draft. <a href="' . esc_url(get_edit_post_link( $post_id )) . '">' . esc_html__('Edit Post', 'text-domain') . '</a></div>';
                }
            }
        }
}
add_shortcode( 'catai_generator_page', 'catai_generator_page' );

//End of code for the article generating page

//Start of code for Social Helper

function catai_social_helper_page() {

    $api_key = get_option( 'catai_api_key' );
    if ( empty( $api_key ) ) {
        echo '<div class="catai-article-generator">';
        echo '<h1>Content Automation Toolkit - Social Helper</h1>';
        echo '<p class="noapimessage">You need to enter a valid OpenAI API key before using the Social Helper. Please visit the <a href="admin.php?page=catai-settings">settings page</a> or <a href="https://beta.openai.com/signup/" target="_blank">signup for a new key</a>.</p>';
        echo '</div>';
        } else {
        $no_api_message = '';

        $show_social_content = false;
    ?>
    <div class="catai-social-helper-form">
        <h1>Content Automation Toolkit - Social Helper</h1>
        <form method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" id="catai_social_helper_form" class="catai-form">
            <label for="post_id">Select a Blog Post:</label><br>
            <select id="post_id" name="post_id" style="width:100%;">
                <?php
                $args = array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'posts_per_page' => -1
                    );
                    $posts = get_posts( $args );
                    foreach ( $posts as $post ) {
                    echo '<option value="' . esc_attr( $post->ID ) . '">' . esc_html( $post->post_title ) . '</option>';
                    }
                    ?>
                    </select>
                    <br><br>
                    <input type="submit" id="catai-generate-btn" class="catai-generate-btn" value="Generate Content">
                    <br><br>
        </form>

<?php
if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    $post_id = intval($_POST['post_id']);
    $post = get_post( $post_id );
    $prompt = sanitize_text_field("Please generate a short summary based on the contents of this blog post '" . $post->post_title . "' and then follow this summary with 15 recommended popular hashtags based on the contents of the blog post and displayed like #item #item #item etc. Do not say the word hashtag or introduce the hashtags just append the list of hashtags after the post summary");
    $api_key = sanitize_text_field(get_option('catai_api_key'));
    $temperature = 0.7;
    $max_tokens = 4000;
    $url = 'https://api.openai.com/v1/engines/text-davinci-003/completions';

    $data = array(
        "prompt" => $prompt,
        "temperature" => $temperature,
        "max_tokens" => $max_tokens,
        "stop" => "#hashtag"
    );

    $response = wp_remote_post( $url, array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body' => json_encode($data),
        'cookies' => array()
    ) );

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_message = wp_remote_retrieve_response_message( $response );
    $response_body = json_decode(wp_remote_retrieve_body( $response ), true);
    if ( $response_code == 200 ) {
        $show_social_content = true;
        $content = $response_body["choices"][0]["text"];
        $content_array = explode("#hashtag", $content);
        $summary = sanitize_text_field($content_array[0]);
        $summary = ltrim($summary, " . ");        
        $hashtags = sanitize_text_field($content_array[1]);
        $hashtags_array = explode(" ", $hashtags);
        $hashtags_final = array();
        foreach ($hashtags_array as $hashtag) {
            if (!empty($hashtag)) {
                $hashtags_final[] = "#" . $hashtag;
            }
        }
        $hashtags_final = implode(" ", $hashtags_final);
    } else {
    // Handle error here
    error_log(print_r($response_body, true));
    echo '<p>Error: ' . esc_html($response_body['message'], ENT_QUOTES) . '</p>';
    }
}

if ($show_social_content):
echo '<div id="catai-social-content">';
echo '<label>Social Media Content:</label><br>';
echo '<textarea id="social-content" style="width: 100%; height: 200px;">'.esc_textarea($summary).' '.esc_textarea($hashtags).PHP_EOL.PHP_EOL.esc_url(get_permalink($post_id)).'</textarea>';
echo '<br><br>';
echo '<button id="catai-copy-btn" class="catai-copy-btn" onClick="document.getElementById(\'social-content\').select(); document.execCommand(\'copy\'); alert(\'Social media content copied to clipboard\');">Copy Contents</button>';
echo '</div>';
endif;
}

echo esc_html($no_api_message);

}

//End of Code for Social Helper

//Start of Code for FAQ

function catai_faq_page() {
    ?>
    <div class="catai-faq-container">
        <h1>Frequently Asked Questions</h1>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">What are the requirements for using this plugin?</div>
            <div class="catai-faq-answer">The plugin requires a server with PHP version 7.3 or higher and WordPress version 4.7 or higher.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">What kind of content can I generate with this plugin?</div>
            <div class="catai-faq-answer">The plugin uses OpenAI's language generation capabilities to generate entire blog posts, summaries and hashtags, as well as social media content for sharing on various platforms.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Does the OpenAI API key cost money?</div>
            <div class="catai-faq-answer">Anyone can signup for an API key for free. Currently OpenAI offers an $18 credit for API usage and API costs are very low, usually just a few cents per API call. You can upgrade to a paid account and set monthly limits to manage your use.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Where do I get my own API key to use this plugin?</div>
            <div class="catai-faq-answer">Signup for free through OpenAI right here: <a href="https://beta.openai.com/signup/api-key" target="_blank">https://beta.openai.com/signup/api-key</a></div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Why does it take so long to generate content?</div>
            <div class="catai-faq-answer">Usually content generates in just a few seconds, but this can extend out to 20-30 seconds depending on a number of factors including your internet connection, server speed, and the current load on the OpenAI ChatGPT system.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Why is the plugin freezing or not returning results sometimes?</div>
            <div class="catai-faq-answer">This is rare, but can happen on occasion. This is typically caused by excessive load on the ChatGPT system. It is also possible that the OpenAI API system could be down. To check the current API status you can go here: <a href="https://status.openai.com/" target="_blank">https://status.openai.com/</a>. You can also try reloading the current page you are on and make your request again. This typically fixes most issues.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Which ChatGPT Model does this plugin use?</div>
            <div class="catai-faq-answer">As of Version 1's launch, we are currently using the latest model from OpenAI which is text-davinci-003. This is currently the most capable model available for ChatGPT-3.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Does the plugin know about current events?</div>
            <div class="catai-faq-answer">The current version of ChatGPT-3 was trained on data up until 2021. Because of this, there is no guarantee that it will be able to generate articles based on more recent events. This is why you should proof read all outputs from the plugin to make sure you are happy with the result.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">I got an error while generating content, now what?</div>
            <div class="catai-faq-answer">Please report all bugs to us at <a href="mailto:info@bpmediaworks.com">info@bpmediaworks.com</a> Be as detailed as possible and we will do what we can to fix the issue in a future release. We have extensively tested the plugin so bugs should be rare but differences in server configuration, WordPress themes and plugins can all cause issues.</div>
        </div>

        <div class="catai-faq-question-answer">
            <div class="catai-faq-question">Why are you giving this away for free?</div>
            <div class="catai-faq-answer">Because we are excited to share this revolutionary AI technology with the masses. OpenAI have created a wonderful tool in ChatGPT and we have simply provided a means of accessing that from within WordPress. That being said, if you really love our work you can always <a href="https://www.paypal.com/donate/?hosted_button_id=FDAJFP6ZJJFUE" target="_blank">donate</a> to help fund future projects.</div>
        </div>

    </div>
    <?php
}

//End of Code for FAQ

function catai_enqueue_scripts() {
        wp_enqueue_script( 'content-automation-toolkit', plugin_dir_url( __FILE__ ) . 'content-automation-toolkit.js', array(), '1.0', true );
        wp_enqueue_style( 'content-automation-toolkit', plugin_dir_url( __FILE__ ) . 'content-automation-toolkit.css', array(), '1.0' );
        wp_localize_script( 'content-automation-toolkit', 'plugin_path', plugins_url( '/content-automation-toolkit-cat/' ) );
}
add_action( 'admin_enqueue_scripts', 'catai_enqueue_scripts' );

?>